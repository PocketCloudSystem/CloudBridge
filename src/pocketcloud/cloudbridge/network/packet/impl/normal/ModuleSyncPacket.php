<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\module\globalchat\GlobalChat;
use pocketcloud\cloudbridge\module\hubcommand\HubCommand;
use pocketcloud\cloudbridge\module\npc\CloudNPCManager;
use pocketcloud\cloudbridge\module\sign\CloudSignManager;
use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;
use pocketcloud\cloudbridge\util\ModuleSettings;

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
        if (ModuleSettings::isSignModuleEnabled()) CloudSignManager::enable();
        else CloudSignManager::disable();

        if (ModuleSettings::isNpcModuleEnabled()) CloudNPCManager::enable();
        else CloudNPCManager::disable();

        if (ModuleSettings::isHubCommandModuleEnabled()) HubCommand::enable();
        else HubCommand::disable();

        if (ModuleSettings::isGlobalChatModuleEnabled()) GlobalChat::enable();
        else GlobalChat::disable();
    }
}