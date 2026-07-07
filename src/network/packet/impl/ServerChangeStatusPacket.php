<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\api\object\server\util\ServerStatus;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;

final class ServerChangeStatusPacket extends CloudPacket implements CloudboundPacket {

    public function __construct(
        private readonly string $serverUuid = "",
        private readonly ?ServerStatus $status = null
    ) {}

    public static function create(string $serverUuid, ServerStatus $status): self {
        return new self($serverUuid, $status);
    }

    public function handle(): void {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->serverUuid, $this->status);
    }

    public function decodePayload(PacketData $packetData): void {}

    public function getServerUuid(): string {
        return $this->serverUuid;
    }

    public function getStatus(): ?ServerStatus {
        return $this->status;
    }
}