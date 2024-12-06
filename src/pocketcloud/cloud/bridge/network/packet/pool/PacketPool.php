<?php

namespace pocketcloud\cloud\bridge\network\packet\pool;

use GlobalLogger;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\DisconnectPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\KeepAlivePacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\LanguageSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\ServerHandshakeRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\ServerHandshakeResponsePacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\CloudNotifyPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\CloudServerSavePacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\CloudServerStatusChangePacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\CloudServerSyncStoragePacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\CloudSyncStoragesPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\CommandSendAnswerPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\CommandSendPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\ConsoleTextPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\LibrarySyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\ModuleSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\PlayerConnectPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\PlayerDisconnectPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\PlayerKickPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\PlayerNotifyUpdatePacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\PlayerSwitchServerPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\PlayerSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\PlayerTextPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\PlayerTransferPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\ServerSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\TemplateSyncPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\CheckPlayerMaintenanceRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\CheckPlayerNotifyRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\CloudServerStartRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\CloudServerStopRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\CheckPlayerMaintenanceResponsePacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\CheckPlayerNotifyResponsePacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\CloudServerStartResponsePacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\CloudServerStopResponsePacket;
use pocketmine\utils\SingletonTrait;
use ReflectionClass;
use ReflectionException;

final class PacketPool {
    use SingletonTrait;

    /** @var array<CloudPacket> */
    private array $packets = [];

    public function __construct() {
        self::setInstance($this);

        $this->registerPacket(KeepAlivePacket::class);
        $this->registerPacket(DisconnectPacket::class);
        $this->registerPacket(ServerHandshakeRequestPacket::class);
        $this->registerPacket(ServerHandshakeResponsePacket::class);
        $this->registerPacket(CommandSendPacket::class);
        $this->registerPacket(CommandSendAnswerPacket::class);
        $this->registerPacket(ConsoleTextPacket::class);
        $this->registerPacket(PlayerConnectPacket::class);
        $this->registerPacket(PlayerDisconnectPacket::class);
        $this->registerPacket(PlayerTextPacket::class);
        $this->registerPacket(PlayerKickPacket::class);
        $this->registerPacket(PlayerNotifyUpdatePacket::class);
        $this->registerPacket(PlayerTransferPacket::class);
        $this->registerPacket(CloudServerSavePacket::class);
        $this->registerPacket(CloudServerStatusChangePacket::class);
        $this->registerPacket(PlayerSwitchServerPacket::class);
        $this->registerPacket(TemplateSyncPacket::class);
        $this->registerPacket(ServerSyncPacket::class);
        $this->registerPacket(PlayerSyncPacket::class);
        $this->registerPacket(CloudServerStartRequestPacket::class);
        $this->registerPacket(CloudServerStartResponsePacket::class);
        $this->registerPacket(CloudServerStopRequestPacket::class);
        $this->registerPacket(CloudServerStopResponsePacket::class);
        $this->registerPacket(CheckPlayerMaintenanceRequestPacket::class);
        $this->registerPacket(CheckPlayerMaintenanceResponsePacket::class);
        $this->registerPacket(CheckPlayerNotifyRequestPacket::class);
        $this->registerPacket(CheckPlayerNotifyResponsePacket::class);
        $this->registerPacket(CloudNotifyPacket::class);
        $this->registerPacket(ModuleSyncPacket::class);
        $this->registerPacket(LibrarySyncPacket::class);
        $this->registerPacket(LanguageSyncPacket::class);
        $this->registerPacket(CloudServerSyncStoragePacket::class);
        $this->registerPacket(CloudSyncStoragesPacket::class);
    }

    public function registerPacket(string $packetClass): void {
        if (!is_subclass_of($packetClass, CloudPacket::class)) return;
        try {
            $this->packets[(new ReflectionClass($packetClass))->getShortName()] = $packetClass;
        } catch (ReflectionException $e) {
            GlobalLogger::get()->logException($e);
        }
    }

    public function getPacketById(string $pid): ?CloudPacket {
        $get = $this->packets[$pid] ?? null;
        return ($get == null ? null : new $get());
    }

    public function getPackets(): array {
        return $this->packets;
    }
}