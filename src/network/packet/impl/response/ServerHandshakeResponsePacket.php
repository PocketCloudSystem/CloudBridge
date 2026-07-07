<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\response;

use pocketcloud\cloud\bridge\network\packet\type\VerificationStatus;
use pocketcloud\cloud\bridge\network\packet\ResponsePacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;

final class ServerHandshakeResponsePacket extends ResponsePacket {

    public function __construct(private ?VerificationStatus $verifyStatus = null) {}

    public static function create(VerificationStatus $verifyStatus): self {
        return new self($verifyStatus);
    }

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->verifyStatus], [fn() => $packetData->readEnum(VerificationStatus::class)]);
    }

    public function handle(): void {}

    public function getVerifyStatus(): ?VerificationStatus {
        return $this->verifyStatus;
    }
}