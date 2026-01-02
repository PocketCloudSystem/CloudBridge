<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class ModuleSyncPacket extends CloudPacket implements ClientboundPacket {

    public function __construct(private array $data = []) {}

    public function handle(): void {}

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->data], [fn() => $packetData->readArray()]);
    }
    public function getData(): array {
        return $this->data;
    }

    public static function create(array $data): self {
        return new self($data);
    }
}