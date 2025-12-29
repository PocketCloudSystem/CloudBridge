<?php

namespace pocketcloud\cloud\bridge\network\packet;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\network\packet\impl\request\ServerHandshakeRequestPacket;
use pocketmine\utils\SingletonTrait;

final class PacketPool {
    use SingletonTrait;

    /** @var array<CloudPacket> */
    private array $packets = [];

    public static function init(): void {
        self::setInstance($pool = new self());
        $pool->register(ServerHandshakeRequestPacket::class);
    }

    public function __construct() {
        self::setInstance($this);
    }

    public function register(string $packetClass): void {
        if (!is_subclass_of($packetClass, CloudPacket::class)) return;
        CloudBridge::getInstance()->getLogger()->debug("Registering packet " . basename($packetClass) . " (" . $packetClass . ")");
        $this->packets[basename($packetClass)] = $packetClass;
    }

    public function get(string $pid): ?CloudPacket {
        $get = $this->packets[$pid] ?? null;
        return ($get == null ? null : new $get());
    }

    public function getAll(): array {
        return $this->packets;
    }
}