<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\request;

use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class ServerStartRequestPacket extends RequestPacket {

    public function __construct(
        private readonly string $template = "",
        private readonly int $count = 0
    ) {}

    public static function create(string $template, int $count): self {
        return new self($template, $count);
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->template, $this->count);
    }

    public function getTemplate(): string {
        return $this->template;
    }

    public function getCount(): int {
        return $this->count;
    }
}