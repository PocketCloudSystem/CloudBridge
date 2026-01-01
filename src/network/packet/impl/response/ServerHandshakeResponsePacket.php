<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\response;

use pocketcloud\cloud\bridge\network\packet\data\VerifyStatus;
use pocketcloud\cloud\bridge\network\packet\ResponsePacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class ServerHandshakeResponsePacket extends ResponsePacket {

    public function __construct(private ?VerifyStatus $verifyStatus = null) {}

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->verifyStatus], [fn() => $packetData->readVerifyStatus()]);
    }

    public function handle(): void {}

    public function getVerifyStatus(): ?VerifyStatus {
        return $this->verifyStatus;
    }
}