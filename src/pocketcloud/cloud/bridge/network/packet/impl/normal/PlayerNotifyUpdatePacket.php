<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\normal;

use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;


class PlayerNotifyUpdatePacket extends CloudPacket {

    public function __construct(
        private string $playerName = "",
        private bool $value = false
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->playerName);
        $packetData->write($this->value);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->playerName = $packetData->readString();
        $this->value = $packetData->readBool();
    }

    public function getPlayerName(): string {
        return $this->playerName;
    }

    public function getValue(): bool {
        return $this->value;
    }

    public function handle(): void {}
}