<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\normal;

use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;


class PlayerSwitchServerPacket extends CloudPacket {

    public function __construct(
        private string $playerName = "",
        private string $newServer = ""
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->playerName);
        $packetData->write($this->newServer);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->playerName = $packetData->readString();
        $this->newServer = $packetData->readString();
    }

    public function getPlayerName(): string {
        return $this->playerName;
    }

    public function getNewServer(): string {
        return $this->newServer;
    }

    public function handle(): void {}
}