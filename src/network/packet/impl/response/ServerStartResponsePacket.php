<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\response;

use pocketcloud\cloud\bridge\network\packet\data\ServerErrorReason;
use pocketcloud\cloud\bridge\network\packet\ResponsePacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class ServerStartResponsePacket extends ResponsePacket {

    public function __construct(private readonly ?ServerErrorReason $errorReason = null) {}

    public function handle(): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->errorReason], [fn() => $packetData->readServerErrorReason()]);
    }

    public function getErrorReason(): ?ServerErrorReason {
        return $this->errorReason;
    }

    public static function create(ServerErrorReason $errorReason): self {
        return new self($errorReason);
    }
}