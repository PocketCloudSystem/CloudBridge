<?php

namespace pocketcloud\cloudbridge\network;

use Exception;
use GlobalLogger;
use pmmp\thread\ThreadSafeArray;
use pocketcloud\cloudbridge\event\network\NetworkCloseEvent;
use pocketcloud\cloudbridge\event\network\NetworkConnectEvent;
use pocketcloud\cloudbridge\event\network\NetworkPacketSendEvent;
use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\handler\PacketSerializer;
use pocketcloud\cloudbridge\util\Address;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\Thread;
use pocketmine\utils\SingletonTrait;
use Socket;

class Network extends Thread {
    use SingletonTrait;

    private Socket $socket;
    private bool $connected = false;

    public function __construct(
        private readonly Address $address,
        private readonly SleeperHandlerEntry $handlerEntry,
        private ThreadSafeArray $buffer
    ) {
        self::setInstance($this);
        GlobalLogger::get()->info("Try to connect to §e" . $this->address . "§r...");
        try {
            $this->connect();
        } catch (Exception $exception) {
            GlobalLogger::get()->critical("Failed to connect to " . $this->address . ": " . trim(socket_strerror(socket_last_error($this->socket))));
            GlobalLogger::get()->logException($exception);
        }
    }

    public function onRun(): void {
        $this->registerClassLoaders();
        while ($this->isConnected()) {
            if ($this->read($buffer, $address, $port) !== false) {
                $this->buffer[] = $buffer;
                $this->handlerEntry->createNotifier()->wakeupSleeper();
            }
        }
    }

    public function connect(): void {
        if ($this->connected) return;
        GlobalLogger::get()->info("Connecting to §b" . $this->address . "§r...");
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (socket_connect($this->socket, $this->address->getAddress(), $this->address->getPort())) {
            (new NetworkConnectEvent($this->address))->call();
            socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, 1024 * 1024 * 8);
            socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, 1024 * 1024 * 8);
            $this->connected = true;
            GlobalLogger::get()->info("Successfully connected to §b" . $this->address . "§r!");
            GlobalLogger::get()->info("§cWaiting for incoming packets...");
        }
    }

    public function write(string $buffer): bool {
        if (!$this->isConnected()) return false;
        return @socket_send($this->socket, $buffer, strlen($buffer), 0) !== false;
    }

    public function read(?string &$buffer, ?string &$address, ?int &$port): bool {
        if (!$this->isConnected()) return false;
        return @socket_recvfrom($this->socket, $buffer, 65535, 0, $address, $port) !== false;
    }

    public function close(): void {
        if ($this->isConnected()) {
            $this->connected = false;
            (new NetworkCloseEvent())->call();
            @socket_close($this->socket);
        }
    }

    public function sendPacket(CloudPacket $packet): bool {
        if ($this->isConnected()) {
            $json = PacketSerializer::encode($packet);
            if ($json !== "") {
                $ev = new NetworkPacketSendEvent($packet);
                $ev->call();
                if (!$ev->isCancelled()) return $this->write($json);
            }
        }
        return false;
    }

    public function getAddress(): Address {
        return $this->address;
    }

    public function getBuffer(): ThreadSafeArray {
        return $this->buffer;
    }

    public function getSocket(): Socket {
        return $this->socket;
    }

    public function isConnected(): bool {
        return $this->connected;
    }
}