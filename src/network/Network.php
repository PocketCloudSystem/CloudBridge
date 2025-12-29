<?php

namespace pocketcloud\cloud\bridge\network;

use LogicException;
use pmmp\thread\ThreadSafeArray;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\event\impl\network\NetworkCloseEvent;
use pocketcloud\cloud\bridge\event\impl\network\NetworkPacketPreSendEvent;
use pocketcloud\cloud\bridge\event\impl\network\NetworkPacketReceiveEvent;
use pocketcloud\cloud\bridge\event\impl\network\NetworkPacketReceivePreProcessEvent;
use pocketcloud\cloud\bridge\event\impl\network\NetworkPacketSendEvent;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
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
use raklib\generic\SocketException;
use Socket;

final class Network extends Thread {
    use SingletonTrait;

    private ThreadSafeArray $buffer;
    private SleeperHandlerEntry $handlerEntry;
    private Socket $socket;
    private bool $connected = false;

    public function __construct(private readonly Address $address) {
        self::setInstance($this);
        $this->buffer = new ThreadSafeArray();

        $this->handlerEntry = Server::getInstance()->getTickSleeper()->addNotifier(function (): void {
            /** @var UnhandledPacket $unhandledPacket */
            while (($unhandledPacket = $this->buffer->shift()) !== null) {
                TrafficMonitorManager::getInstance()->pushBytes(TrafficMonitorManager::TRAFFIC_NETWORK, $bytes = $unhandledPacket->getBytes(), TrafficMonitor::REGULAR_MODE_IN);
                TrafficMonitorManager::getInstance()->callHandlers(
                    TrafficMonitorManager::TRAFFIC_NETWORK,
                    TrafficMonitor::REGULAR_MODE_IN,
                    $unhandledPacket->getBuffer(), $bytes, $unhandledPacket->getAddress()
                );

                ($ev = new NetworkPacketReceivePreProcessEvent($unhandledPacket->getBuffer(), $encryption = CloudEnvironmentConfig::isNetworkEncryptionEnabled(), $unhandledPacket->getAddress()))->call();
                if ($ev->isCancelled()) return;

                if (($packet = $unhandledPacket->buildCloudPacket($encryption)) !== null) {
                    TrafficMonitorManager::getInstance()->callHandlers(
                        TrafficMonitorManager::TRAFFIC_NETWORK,
                        NetworkTrafficMonitor::parsePacketMode(NetworkTrafficMonitor::NETWORK_MODE_PACKET_IN, $packet::class),
                        $packet, $unhandledPacket->getAddress()
                    );

                    ($ev = new NetworkPacketReceiveEvent($packet, $unhandledPacket->getAddress()))->call();
                    if ($ev->isCancelled()) return;
                    $packet->handle();

                    if ($packet instanceof ResponsePacket) {
                        RequestManager::getInstance()->resolve($packet);
                        RequestManager::getInstance()->remove($packet->getRequestId());
                    }
                } else {
                    CloudBridge::getInstance()->getLogger()->warning("§cReceived an unknown packet from the cloud!");
                    CloudBridge::getInstance()->getLogger()->debug($unhandledPacket->getBuffer());
                }
            }
        });
    }

    public function init(): void {
        if ($this->connected) throw new LogicException("Socket has already been established");
        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) throw new SocketException(socket_strerror(socket_last_error()));
        $this->socket = $socket;
        if (@socket_connect($socket, $this->address->getAddress(), $this->address->getPort())) {
            $this->connected = true;
            socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, 1024 * 1024 * 8);
            socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, 1024 * 1024 * 8);
            socket_set_block($this->socket);
        } else throw new SocketException(socket_strerror(socket_last_error()));

        CloudBridge::getInstance()->getLogger()->info("Successfully connected to §b" . $this->address . "§r!");
        CloudBridge::getInstance()->getLogger()->info("§cWaiting for incoming packets...");
    }

    protected function onRun(): void {
        while ($this->connected && $this->isRunning()) {
            $read = [$this->socket];
            $write = $except = [];

            if (socket_select($read, $write, $except, 0, 50 * 1000) > 0) {
                if ($this->read($bytes, $buffer, $address, $port)) {
                    $this->buffer[] = new UnhandledPacket($buffer, Address::create($address, $port), $bytes);
                }
            }
        }
    }

    public function sendPacket(CloudboundPacket $packet): bool {
        if (!$this->connected) return false;
        ($ev = new NetworkPacketPreSendEvent($packet, $this->address))->call();
        if ($ev->isCancelled()) return false;
        $buffer = PacketSerializer::encode($packet, CloudEnvironmentConfig::isNetworkEncryptionEnabled());
        $success = $this->write($buffer);
        TrafficMonitorManager::getInstance()->callHandlers(
            TrafficMonitorManager::TRAFFIC_NETWORK,
            NetworkTrafficMonitor::parsePacketMode(NetworkTrafficMonitor::NETWORK_MODE_PACKET_OUT, $packet::class),
            $packet, $this->address, $success
        );

        new NetworkPacketSendEvent($packet, $this->address, $success)->call();
        return $success;
    }

    public function write(string $buffer): bool {
        if (!$this->connected) return false;
        $sent = @socket_send($this->socket, $buffer, $bytes = strlen($buffer), 0);
        if ($sent === false || $sent <= 0) return false;

        TrafficMonitorManager::getInstance()->pushBytes(TrafficMonitorManager::TRAFFIC_NETWORK, $sent, TrafficMonitor::REGULAR_MODE_OUT);
        TrafficMonitorManager::getInstance()->callHandlers(
            TrafficMonitorManager::TRAFFIC_NETWORK,
            TrafficMonitor::REGULAR_MODE_OUT,
            $buffer,
            $sent,
            $this->address
        );

        return $sent === $bytes;
    }

    public function read(?int &$bytes, ?string &$buffer, ?string &$address, ?int &$port): bool {
        if (!$this->connected) return false;
        $result = @socket_recvfrom($this->socket, $buffer, 65535, 0, $address, $port);
        if (!$result === false || $result === 0) {
            $bytes = 0;
            return false;
        }

        $bytes = $result;
        return true;
    }

    public function close(): void {
        if (!$this->connected) return;
        @socket_close($this->socket);
        $this->connected = false;
        new NetworkCloseEvent()->call();
    }
}