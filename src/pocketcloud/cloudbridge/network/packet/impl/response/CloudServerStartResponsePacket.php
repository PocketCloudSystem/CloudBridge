<?php

namespace pocketcloud\cloudbridge\network\packet\impl\response;

use pocketcloud\cloudbridge\network\packet\impl\types\ErrorReason;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class CloudServerStartResponsePacket extends ResponsePacket {

    public function __construct(private ?ErrorReason $errorReason = null) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeErrorReason($this->errorReason);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->errorReason = $packetData->readErrorReason();
    }

    public function getErrorReason(): ?ErrorReason {
        return $this->errorReason;
    }

    public function handle(): void {}
}