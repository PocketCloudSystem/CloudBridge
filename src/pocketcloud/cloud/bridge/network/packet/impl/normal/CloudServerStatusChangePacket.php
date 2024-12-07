<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\normal;

use pocketcloud\cloud\bridge\api\object\server\status\ServerStatus;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;


class CloudServerStatusChangePacket extends CloudPacket {

    public function __construct(private ?ServerStatus $newStatus = null) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeServerStatus($this->newStatus);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->newStatus = $packetData->readServerStatus();
    }

    public function getNewStatus(): ?ServerStatus {
        return $this->newStatus;
    }

    public function handle(): void {}
}