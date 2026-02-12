<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class CloudSyncServerStoragePacket extends CloudPacket implements CloudboundPacket {

    public function __construct(private readonly array $data = []) {}

    public function handle(): void {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->data);
    }

    public function decodePayload(PacketData $packetData): void {}

    public function getData(): array {
        return $this->data;
    }

    public static function create(array $data): self {
        return new self($data);
    }
}