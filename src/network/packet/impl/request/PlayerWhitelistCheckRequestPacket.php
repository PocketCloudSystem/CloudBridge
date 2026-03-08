<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\request;

use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class PlayerWhitelistCheckRequestPacket extends RequestPacket {

    public function __construct(private readonly string $player = "") {}

    public static function create(string $player): self {
        return new self($player);
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->player);
    }

    public function getPlayer(): string {
        return $this->player;
    }
}