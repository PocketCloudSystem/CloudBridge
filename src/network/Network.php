<?php

namespace pocketcloud\cloud\bridge\network;

use JsonException;
use LogicException;
use pmmp\thread\ThreadSafeArray;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\event\network\NetworkCloseEvent;
use pocketcloud\cloud\bridge\event\network\NetworkPacketPreSendEvent;
use pocketcloud\cloud\bridge\event\network\NetworkPacketReceiveEvent;
use pocketcloud\cloud\bridge\event\network\NetworkPacketReceivePreProcessEvent;
use pocketcloud\cloud\bridge\event\network\NetworkPacketSentEvent;
use pocketcloud\cloud\bridge\exception\PacketException;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\PacketPool;
use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\network\packet\ResponsePacket;
use pocketcloud\cloud\bridge\network\packet\UnhandledPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketSerializer;
use pocketcloud\cloud\bridge\network\request\RequestManager;
use pocketcloud\cloud\bridge\traffic\impl\NetworkTrafficMonitor;
use pocketcloud\cloud\bridge\traffic\TrafficMonitor;
use pocketcloud\cloud\bridge\traffic\TrafficMonitorManager;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use pocketcloud\cloud\bridge\util\net\Address;
use pocketmine\Server;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\Thread;
use pocketmine\utils\SingletonTrait;
use RuntimeException;
use Socket;

final class Network extends Thread {
    use SingletonTrait;

    private ThreadSafeArray $buffer;
    private ThreadSafeArray $sendBuffer;
    private SleeperHandlerEntry $handlerEntry;
    private Socket $socket;
    private bool $connected = false;

    public function __construct(private readonly Address $address) {
        self::setInstance($this);
        $this->buffer = new ThreadSafeArray();
        $this->sendBuffer = new ThreadSafeArray();

        PacketPool::init();

        $this->handlerEntry = Server::getInstance()->getTickSleeper()->addNotifier(function (): void {
            while (($unhandledPacketData = $this->buffer->shift()) !== null) {
                if (!$this->connected) return;
                [$address, $port, $buffer, $bytes] = $unhandledPacketData;
                $unhandledPacket = new UnhandledPacket($buffer, Address::create($address, $port), $bytes);

                TrafficMonitorManager::getInstance()->pushBytes(TrafficMonitorManager::TRAFFIC_NETWORK, $unhandledPacket->getBytes(), TrafficMonitor::REGULAR_MODE_IN);

                ($ev = new NetworkPacketReceivePreProcessEvent($this, $unhandledPacket->getAddress(), $unhandledPacket->getBuffer(), $encryption = CloudEnvironmentConfig::isNetworkEncryptionEnabled()))->call();
                if ($ev->isCancelled()) continue;

                try {
                    if (($packet = $unhandledPacket->buildCloudPacket($encryption, CloudEnvironmentConfig::getNetworkAuthKey())) !== null) {
                        TrafficMonitorManager::getInstance()->callHandlers(
                            TrafficMonitorManager::TRAFFIC_NETWORK,
                            NetworkTrafficMonitor::parsePacketMode(NetworkTrafficMonitor::NETWORK_MODE_PACKET_IN, $packet::class),
                            $packet, $unhandledPacket->getAddress()
                        );

                        ($ev = new NetworkPacketReceiveEvent($this, $unhandledPacket->getAddress(), $packet))->call();
                        if ($ev->isCancelled()) continue;
                        $packet->handle();

                        if ($packet instanceof ResponsePacket) {
                            RequestManager::getInstance()->resolve($packet);
                            RequestManager::getInstance()->remove($packet->getRequestId());
                        }
                    }
                } catch (PacketException|JsonException $e) {
                    CloudBridge::getInstance()->getLogger()->logException($e);
                }
            }
        });
    }

    public function init(): void {
        if ($this->connected) throw new LogicException("Socket has already been established");

        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) throw new RuntimeException(socket_strerror(socket_last_error()));

        socket_set_option($socket, SOL_TCP, TCP_NODELAY, 1);

        if (@socket_connect($socket, $this->address->getAddress(), $this->address->getPort())) {
            $this->socket = $socket;
            $this->connected = true;
            socket_set_nonblock($this->socket);
        } else {
            throw new RuntimeException(socket_strerror(socket_last_error()));
        }

        CloudBridge::getInstance()->getLogger()->info("Successfully connected to Cloud via TCP at §b" . $this->address);
    }

    protected function onRun(): void {
        $notifier = $this->handlerEntry->createNotifier();
        $readBuffer = "";

        while ($this->connected && !$this->isKilled) {
            while (($buffer = $this->sendBuffer->shift()) !== null) {
                if (!$this->tcpWrite($buffer)) {
                    $this->connected = false;
                    break 2;
                }
            }

            $read = [$this->socket];
            $write = $except = [];
            if (socket_select($read, $write, $except, 0, 50000) > 0) {
                $chunk = "";
                $res = socket_recv($this->socket, $chunk, 65535, 0);

                if ($res === 0 || $res === false) {
                    $this->connected = false;
                    break;
                }

                $readBuffer .= $chunk;

                while (strlen($readBuffer) >= 4) {
                    $length = unpack("N", substr($readBuffer, 0, 4))[1];
                    if (strlen($readBuffer) < 4 + $length) break;

                    $payload = substr($readBuffer, 4, $length);
                    $readBuffer = substr($readBuffer, 4 + $length);

                    $this->buffer[] = ThreadSafeArray::fromArray([
                        $this->address->getAddress(),
                        $this->address->getPort(),
                        $payload,
                        $length
                    ]);
                    $notifier->wakeupSleeper();
                }
            }
        }

        @socket_close($this->socket);
        $notifier->wakeupSleeper();
    }

    private function tcpWrite(string $buffer): bool {
        $framed = pack("N", strlen($buffer)) . $buffer;
        $total = strlen($framed);
        $sent = 0;
        while ($sent < $total) {
            $result = @socket_write($this->socket, substr($framed, $sent), $total - $sent);
            if ($result === false) return false;
            $sent += $result;
        }
        return true;
    }

    public function sendPacket(CloudboundPacket $packet): bool {
        if (!$this->connected) return false;

        ($ev = new NetworkPacketPreSendEvent($this, $this->address, $packet))->call();
        if ($ev->isCancelled()) return false;

        $buffer = PacketSerializer::encode($packet, CloudEnvironmentConfig::isNetworkEncryptionEnabled(), CloudEnvironmentConfig::getNetworkAuthKey());
        if ($buffer === null) return false;

        $this->sendBuffer[] = $buffer;

        TrafficMonitorManager::getInstance()->pushBytes(TrafficMonitorManager::TRAFFIC_NETWORK, strlen($buffer), TrafficMonitor::REGULAR_MODE_OUT);
        new NetworkPacketSentEvent($this, $this->address, $packet, true)->call();

        return true;
    }

    public function close(): void {
        $this->connected = false;
        $this->quit();
        new NetworkCloseEvent($this)->call();
    }
}