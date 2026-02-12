<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class ProxyPlayerTransferPacket extends CloudPacket implements CloudboundPacket {

    public function __construct(
        private readonly string $player = "",
        private readonly string $server = ""
    ) {}

    public function handle(): void {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->player, $this->server);
    }

    public function decodePayload(PacketData $packetData): void {}

    public function getPlayer(): string {
        return $this->player;
    }

    public function getServer(): string {
        return $this->server;
    }

    public static function create(string $player, string $server): self {
        return new self($player, $server);
    }
}