<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\response;

use pocketcloud\cloud\bridge\network\packet\type\ServerErrorReason;
use pocketcloud\cloud\bridge\network\packet\ResponsePacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;

final class ServerStartResponsePacket extends ResponsePacket {

    public function __construct(private ?ServerErrorReason $errorReason = null) {}

    public static function create(ServerErrorReason $errorReason): self {
        return new self($errorReason);
    }

    public function handle(): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->errorReason], [fn() => $packetData->readEnum(ServerErrorReason::class)]);
    }

    public function getErrorReason(): ?ServerErrorReason {
        return $this->errorReason;
    }
}