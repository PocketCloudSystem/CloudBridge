<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\request;

use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;

final class ServerStopRequestPacket extends RequestPacket {

    public function __construct(
        private readonly string $server = "",
        private readonly bool $forcefully = false
    ) {}

    public static function create(string $server, bool $forcefully): self {
        return new self($server, $forcefully);
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->server, $this->forcefully);
    }

    public function getServer(): string {
        return $this->server;
    }

    public function isForcefully(): bool {
        return $this->forcefully;
    }
}