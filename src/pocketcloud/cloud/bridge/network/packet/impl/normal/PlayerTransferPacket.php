<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\normal;

use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;


class PlayerTransferPacket extends CloudPacket {

    public function __construct(
        private string $player = "",
        private string $server = ""
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->player)->write($this->server);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->player = $packetData->readString();
        $this->server = $packetData->readString();
    }

    public function handle(): void {}
}