<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\response;

use pocketcloud\cloud\network\packet\data\PacketData;
use pocketcloud\cloud\network\packet\impl\type\VerifyStatus;
use pocketcloud\cloud\network\packet\ResponsePacket;

final class ServerHandshakeResponsePacket extends ResponsePacket {

    public function __construct(
        private ?VerifyStatus $verifyStatus = null,
        private string $prefix = ""
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeVerifyStatus($this->verifyStatus)
            ->write($this->prefix);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->verifyStatus = $packetData->readVerifyStatus();
        $this->prefix = $packetData->readString();
    }

    public function getVerifyStatus(): ?VerifyStatus {
        return $this->verifyStatus;
    }

    public function getPrefix(): string {
        return $this->prefix;
    }
}