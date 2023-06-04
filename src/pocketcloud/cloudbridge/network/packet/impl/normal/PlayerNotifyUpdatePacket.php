<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class PlayerNotifyUpdatePacket extends CloudPacket {

    public function __construct(
        private string $playerName = "",
        private bool $value = false
    ) {}

    public function encodePayload(PacketData $packetData) {
        $packetData->write($this->playerName);
        $packetData->write($this->value);
    }

    public function decodePayload(PacketData $packetData) {
        $this->playerName = $packetData->readString();
        $this->value = $packetData->readBool();
    }

    public function getPlayerName(): string {
        return $this->playerName;
    }

    public function getValue(): bool {
        return $this->value;
    }

    public function handle() {}
}