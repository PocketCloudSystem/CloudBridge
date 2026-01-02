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
use pocketcloud\cloud\bridge\network\packet\impl\LibrarySyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\ModuleSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\PlayerSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\ServerGroupSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\ServerSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\TemplateSyncPacket;
use pocketmine\utils\SingletonTrait;
use ReflectionClass;
use ReflectionException;

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
        $this->register(LibrarySyncPacket::class);
        $this->register(ModuleSyncPacket::class);
        $this->register(TemplateSyncPacket::class);
        $this->register(ServerSyncPacket::class);
        $this->register(ServerGroupSyncPacket::class);
        $this->register(PlayerSyncPacket::class);
    }

    public function register(string $packetClass): void {
        if (!is_subclass_of($packetClass, CloudPacket::class)) return;
        try {
            CloudBridge::getInstance()->getLogger()->debug("Registering packet " . ($packetName = new ReflectionClass($packetClass)->getShortName()) . " (" . $packetClass . ")");
            $this->packets[$packetName] = $packetClass;
        } catch (ReflectionException $exception) {
            CloudBridge::getInstance()->getLogger()->warning("Failed to register packet " . $packetClass);
            CloudBridge::getInstance()->getLogger()->logException($exception);
        }
    }

    public function get(string $pid): ?CloudPacket {
        $get = $this->packets[$pid] ?? null;
        return ($get == null ? null : new $get());
    }

    public function getAll(): array {
        return $this->packets;
    }
}