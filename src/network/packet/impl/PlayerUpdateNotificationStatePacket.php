<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class PlayerUpdateNotificationStatePacket extends CloudPacket implements CloudboundPacket {

    public function __construct(
        private readonly string $player = "",
        private readonly bool $value = false
    ) {}

    public function handle(): void {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->player, $this->value);
    }

    public function decodePayload(PacketData $packetData): void {}

    public function getPlayer(): string {
        return $this->player;
    }

    public function isValue(): bool {
        return $this->value;
    }

    public static function create(string $player, bool $value): self {
        return new self($player, $value);
    }
}