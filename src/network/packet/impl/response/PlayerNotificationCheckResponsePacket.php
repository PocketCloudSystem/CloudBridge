<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\response;

use pocketcloud\cloud\bridge\network\packet\ResponsePacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class PlayerNotificationCheckResponsePacket extends ResponsePacket {

    public function __construct(private bool $enabled = false) {}

    public static function create(bool $enabled): self {
        return new self($enabled);
    }

    public function handle(): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAll($this->enabled);
    }

    public function isEnabled(): bool {
        return $this->enabled;
    }
}