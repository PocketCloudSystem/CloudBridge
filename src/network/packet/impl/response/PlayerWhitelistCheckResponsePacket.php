<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\response;

use pocketcloud\cloud\bridge\network\packet\ResponsePacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class PlayerWhitelistCheckResponsePacket extends ResponsePacket {

    public function __construct(private bool $whitelisted = false) {}

    public static function create(bool $whitelisted): self {
        return new self($whitelisted);
    }

    public function handle(): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAll($this->whitelisted);
    }

    public function isWhitelisted(): bool {
        return $this->whitelisted;
    }
}