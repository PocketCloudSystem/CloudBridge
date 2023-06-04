<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class PlayerDisconnectPacket extends CloudPacket {

    public function __construct(private ?string $playerName = "") {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->playerName);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->playerName = $packetData->readString();
    }

    public function getPlayer(): string {
        return $this->playerName;
    }

    public function handle() {}
}