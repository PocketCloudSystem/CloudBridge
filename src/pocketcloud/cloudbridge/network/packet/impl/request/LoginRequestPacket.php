<?php

namespace pocketcloud\cloudbridge\network\packet\impl\request;

use pocketcloud\cloudbridge\network\packet\RequestPacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class LoginRequestPacket extends RequestPacket {

    public function __construct(
        private string $serverName = "",
        private int $processId = 0,
        private int $maxPlayerCount = 0
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->serverName);
        $packetData->write($this->processId);
        $packetData->write($this->maxPlayerCount);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->serverName = $packetData->readString();
        $this->processId = $packetData->readInt();
        $this->maxPlayerCount = $packetData->readInt();
    }
}