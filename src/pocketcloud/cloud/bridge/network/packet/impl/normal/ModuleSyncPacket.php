<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\normal;

use pocketcloud\cloud\bridge\module\ModuleManager;

use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;
use pocketcloud\cloud\bridge\util\ModuleSettings;

class ModuleSyncPacket extends CloudPacket {

    private array $data = [];

    public function __construct() {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->data);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->data = $packetData->readArray();
    }

    public function getData(): array {
        return $this->data;
    }

    public function handle(): void {
        ModuleSettings::sync($this->data);
        ModuleManager::getInstance()->syncModuleStates();
    }
}