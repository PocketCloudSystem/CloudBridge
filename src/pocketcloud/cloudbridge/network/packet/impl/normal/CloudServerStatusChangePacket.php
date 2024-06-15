<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\api\object\server\status\ServerStatus;
use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

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