<?php

namespace pocketcloud\cloudbridge\network;

use pocketcloud\cloudbridge\event\NetworkCloseEvent;
use pocketcloud\cloudbridge\event\NetworkConnectEvent;
use pocketcloud\cloudbridge\event\NetworkPacketSendEvent;
use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\handler\encoder\PacketEncoder;
use pocketcloud\cloudbridge\network\packet\handler\PacketHandler;
use pocketcloud\cloudbridge\network\packet\pool\PacketPool;
use pocketcloud\cloudbridge\network\request\RequestManager;
use pocketcloud\cloudbridge\utils\Address;
use pocketcloud\cloudbridge\network\packet\listener\PacketListener;
use pocketmine\utils\SingletonTrait;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\Thread;

class Network extends Thread {
    use SingletonTrait;

    private \Socket $socket;
    private bool $connected = false;

    public function __construct(private Address $address, private SleeperNotifier $sleeperNotifier, private \ThreadedArray $buffer) {
        self::setInstance($this);

        \GlobalLogger::get()->info("Try to connect to §e" . $this->address . "§r...");
        $this->connect();
    }

    public function onRun(): void {
        $this->registerClassLoaders();
        while ($this->isConnected()) {
            if ($this->read($buffer, $address, $port) !== false) {
                $this->buffer[] = $buffer;
                $this->sleeperNotifier->wakeupSleeper();
            }
        }
    }

    public function connect() {
        if ($this->connected) return;
        $this->socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        \GlobalLogger::get()->info("Connecting to §b" . $this->address . "§r...");
        if (@socket_connect($this->socket, $this->address->getAddress(), $this->address->getPort())) {
            (new NetworkConnectEvent($this->address))->call();
            @socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, 1024 * 1024 * 8);
            @socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, 1024 * 1024 * 8);
            $this->connected = true;
            \GlobalLogger::get()->info("Successfully connected to §b" . $this->address . "§r!");
            \GlobalLogger::get()->info("§cWaiting for incoming packets...");
        } else {
            $error = socket_last_error($this->socket);
            throw new \Exception("Failed to connect to $this->address: " . trim(socket_strerror($error)), $error);
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

    public function close() {
        if ($this->isConnected()) {
            $this->connected = false;
            (new NetworkCloseEvent())->call();
            @socket_close($this->socket);
        }
    }

    public function sendPacket(CloudPacket $packet): bool {
        if ($this->isConnected()) {
            $json = PacketEncoder::encode($packet);
            if ($json !== false) {
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

    public function getBuffer(): \ThreadedArray {
        return $this->buffer;
    }

    public function getSocket(): \Socket {
        return $this->socket;
    }

    public function isConnected(): bool {
        return $this->connected;
    }
}