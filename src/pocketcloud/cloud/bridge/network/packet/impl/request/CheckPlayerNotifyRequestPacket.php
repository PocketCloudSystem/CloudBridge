<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\request;

use pocketcloud\cloud\bridge\network\packet\data\PacketData;
use pocketcloud\cloud\bridge\network\packet\RequestPacket;

class CheckPlayerNotifyRequestPacket extends RequestPacket {

    public function __construct(private string $player = "") {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->player);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->player = $packetData->readString();
    }

    public function getPlayer(): string {
        return $this->player;
    }
}