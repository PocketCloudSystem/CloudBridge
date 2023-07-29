<?php

namespace pocketcloud\cloudbridge\network\packet\impl\response;

use pocketcloud\cloudbridge\network\packet\impl\types\VerifyStatus;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class LoginResponsePacket extends ResponsePacket {

    public function __construct(private ?VerifyStatus $status = null) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeVerifyStatus($this->status);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->status = $packetData->readVerifyStatus();
    }

    public function getStatus(): ?VerifyStatus {
        return $this->status;
    }

    public function handle(): void {}
}