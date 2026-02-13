<?php

namespace pocketcloud\cloud\bridge\network\packet;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\network\packet\impl\CloudNotificationPacket;
use pocketcloud\cloud\bridge\network\packet\impl\CloudSyncServerStoragePacket;
use pocketcloud\cloud\bridge\network\packet\impl\CommandAnswerPacket;
use pocketcloud\cloud\bridge\network\packet\impl\CommandExecutePacket;
use pocketcloud\cloud\bridge\network\packet\impl\DisconnectPacket;
use pocketcloud\cloud\bridge\network\packet\impl\KeepAlivePacket;
use pocketcloud\cloud\bridge\network\packet\impl\LanguageSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\MaintenanceListSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\NotificationListSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\PlayerConnectPacket;
use pocketcloud\cloud\bridge\network\packet\impl\PlayerDisconnectPacket;
use pocketcloud\cloud\bridge\network\packet\impl\PlayerKickPacket;
use pocketcloud\cloud\bridge\network\packet\impl\PlayerUpdateNotificationStatePacket;
use pocketcloud\cloud\bridge\network\packet\impl\PlayerTransferPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\PlayerNotificationCheckRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\PlayerWhitelistCheckRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\ServerHandshakeRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\PlayerNotificationCheckResponsePacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\PlayerWhitelistCheckResponsePacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\ServerHandshakeResponsePacket;
use pocketcloud\cloud\bridge\network\packet\impl\LibrarySyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\ModuleSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\PlayerSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\ServerChangeStatusPacket;
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
        $this->register(PlayerConnectPacket::class);
        $this->register(PlayerDisconnectPacket::class);
        $this->register(PlayerKickPacket::class);
        $this->register(PlayerNotificationCheckRequestPacket::class);
        $this->register(PlayerNotificationCheckResponsePacket::class);
        $this->register(PlayerWhitelistCheckRequestPacket::class);
        $this->register(PlayerWhitelistCheckResponsePacket::class);
        $this->register(PlayerUpdateNotificationStatePacket::class);
        $this->register(MaintenanceListSyncPacket::class);
        $this->register(NotificationListSyncPacket::class);
        $this->register(ServerChangeStatusPacket::class);
        $this->register(CloudSyncServerStoragePacket::class);
        $this->register(PlayerTransferPacket::class);
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