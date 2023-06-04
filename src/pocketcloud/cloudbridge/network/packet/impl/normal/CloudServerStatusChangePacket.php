<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;
use pocketcloud\cloudbridge\api\server\status\ServerStatus;

class CloudServerStatusChangePacket extends CloudPacket {

    public function __construct(private ?ServerStatus $newStatus = null) {}

    public function encodePayload(PacketData $packetData) {
        $packetData->writeServerStatus($this->newStatus);
    }

    public function decodePayload(PacketData $packetData) {
        $this->newStatus = $packetData->readServerStatus();
    }

    public function getNewStatus(): ?ServerStatus {
        return $this->newStatus;
    }

    public function handle() {}
}