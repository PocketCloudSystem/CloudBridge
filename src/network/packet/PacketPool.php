<?php

namespace pocketcloud\cloud\bridge\network\packet;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\network\packet\impl\CloudNotificationPacket;
use pocketcloud\cloud\bridge\network\packet\impl\CommandAnswerPacket;
use pocketcloud\cloud\bridge\network\packet\impl\CommandExecutePacket;
use pocketcloud\cloud\bridge\network\packet\impl\DisconnectPacket;
use pocketcloud\cloud\bridge\network\packet\impl\KeepAlivePacket;
use pocketcloud\cloud\bridge\network\packet\impl\LanguageSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\ServerHandshakeRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\ServerHandshakeResponsePacket;
use pocketmine\utils\SingletonTrait;
use ReflectionClass;

final class PacketPool {
    use SingletonTrait;

    /** @var array<CloudPacket> */
    private array $packets = [];

    public static function init(): void {
        self::setInstance(new self());
    }

    public function __construct() {
        self::setInstance($this);
        $this->register(ServerHandshakeRequestPacket::class);
        $this->register(ServerHandshakeResponsePacket::class);
        $this->register(DisconnectPacket::class);
        $this->register(CloudNotificationPacket::class);
        $this->register(KeepAlivePacket::class);
        $this->register(CommandExecutePacket::class);
        $this->register(CommandAnswerPacket::class);
        $this->register(LanguageSyncPacket::class);
    }

    public function register(string $packetClass): void {
        if (!is_subclass_of($packetClass, CloudPacket::class)) return;
        CloudBridge::getInstance()->getLogger()->debug("Registering packet " . ($packetName = new ReflectionClass($packetClass)->getShortName()) . " (" . $packetClass . ")");
        $this->packets[$packetName] = $packetClass;
    }

    public function get(string $pid): ?CloudPacket {
        $get = $this->packets[$pid] ?? null;
        return ($get == null ? null : new $get());
    }

    public function getAll(): array {
        return $this->packets;
    }
}