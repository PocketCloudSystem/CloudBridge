<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\request;

use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;

final class ServerSaveRequestPacket extends RequestPacket {

    public function __construct(private readonly string $server = "") {}

    public static function create(string $server): self {
        return new self($server);
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->server);
    }

    public function getServer(): string {
        return $this->server;
    }
}