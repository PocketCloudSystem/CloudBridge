<?php

namespace pocketcloud\cloudbridge\network\packet\pool;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\CloudServerSavePacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\CloudServerStatusChangePacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\CommandSendPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\DisconnectPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalPlayerRegisterPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalPlayerUnregisterPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalPlayerUpdatePacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalServerRegisterPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalServerUnregisterPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalServerUpdatePacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalTemplateRegisterPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalTemplateUnregisterPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalTemplateUpdatePacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\NotifyPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerConnectPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerDisconnectPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerKickPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerNotifyUpdatePacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerTextPacket;
use pocketcloud\cloudbridge\network\packet\impl\request\CheckPlayerNotifyRequestPacket;
use pocketcloud\cloudbridge\network\packet\impl\request\CloudServerStartRequestPacket;
use pocketcloud\cloudbridge\network\packet\impl\request\CloudServerStopRequestPacket;
use pocketcloud\cloudbridge\network\packet\impl\request\LoginRequestPacket;
use pocketcloud\cloudbridge\network\packet\impl\response\CheckPlayerMaintenanceResponsePacket;
use pocketcloud\cloudbridge\network\packet\impl\response\CheckPlayerNotifyResponsePacket;
use pocketcloud\cloudbridge\network\packet\impl\response\CloudServerStartResponsePacket;
use pocketcloud\cloudbridge\network\packet\impl\response\LoginResponsePacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\KeepALivePacket;
use pocketcloud\cloudbridge\network\packet\impl\response\CloudServerStopResponsePacket;
use pocketcloud\cloudbridge\network\packet\impl\request\CheckPlayerMaintenanceRequestPacket;
use pocketmine\utils\SingletonTrait;

class PacketPool {
    use SingletonTrait;

    /** @var array<CloudPacket> */
    private array $packets = [];

    public function __construct() {
        self::setInstance($this);
        $this->registerPacket(LoginRequestPacket::class);
        $this->registerPacket(LoginResponsePacket::class);
        $this->registerPacket(DisconnectPacket::class);
        $this->registerPacket(KeepALivePacket::class);
        $this->registerPacket(CommandSendPacket::class);
        $this->registerPacket(PlayerTextPacket::class);
        $this->registerPacket(LocalPlayerRegisterPacket::class);
        $this->registerPacket(LocalPlayerUpdatePacket::class);
        $this->registerPacket(LocalPlayerUnregisterPacket::class);
        $this->registerPacket(LocalServerRegisterPacket::class);
        $this->registerPacket(LocalServerUpdatePacket::class);
        $this->registerPacket(LocalServerUnregisterPacket::class);
        $this->registerPacket(LocalTemplateRegisterPacket::class);
        $this->registerPacket(LocalTemplateUpdatePacket::class);
        $this->registerPacket(LocalTemplateUnregisterPacket::class);
        $this->registerPacket(PlayerConnectPacket::class);
        $this->registerPacket(PlayerDisconnectPacket::class);
        $this->registerPacket(NotifyPacket::class);
        $this->registerPacket(PlayerNotifyUpdatePacket::class);
        $this->registerPacket(CheckPlayerNotifyRequestPacket::class);
        $this->registerPacket(CheckPlayerNotifyResponsePacket::class);
        $this->registerPacket(CloudServerStartRequestPacket::class);
        $this->registerPacket(CloudServerStartResponsePacket::class);
        $this->registerPacket(CloudServerStopRequestPacket::class);
        $this->registerPacket(CloudServerStopResponsePacket::class);
        $this->registerPacket(CloudServerSavePacket::class);
        $this->registerPacket(CloudServerStatusChangePacket::class);
        $this->registerPacket(CheckPlayerMaintenanceRequestPacket::class);
        $this->registerPacket(CheckPlayerMaintenanceResponsePacket::class);
        $this->registerPacket(PlayerKickPacket::class);
    }

    public function registerPacket(string $packetClass): void {
        if (!is_subclass_of($packetClass, CloudPacket::class)) return;
        $this->packets[(new \ReflectionClass($packetClass))->getShortName()] = $packetClass;
    }

    public function getPacketById(string $identifier): ?CloudPacket {
        $get = $this->packets[$identifier] ?? null;
        return ($get == null ? null : new $get());
    }

    public function getPackets(): array {
        return $this->packets;
    }
}