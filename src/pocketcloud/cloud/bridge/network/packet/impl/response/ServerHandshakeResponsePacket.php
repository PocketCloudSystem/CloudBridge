<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\response;

use pocketcloud\cloud\bridge\network\packet\data\PacketData;
use pocketcloud\cloud\bridge\network\packet\impl\type\VerifyStatus;
use pocketcloud\cloud\bridge\network\packet\ResponsePacket;

final class ServerHandshakeResponsePacket extends ResponsePacket {

    public function __construct(
        private ?VerifyStatus $verifyStatus = null
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeVerifyStatus($this->verifyStatus);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->verifyStatus = $packetData->readVerifyStatus();
    }

    public function getVerifyStatus(): ?VerifyStatus {
        return $this->verifyStatus;
    }

    public function handle(): void {}
}