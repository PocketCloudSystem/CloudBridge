<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\request;

use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class PlayerNotificationCheckRequestPacket extends RequestPacket {

    public function __construct(private readonly string $player = "") {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->player);
    }

    public function getPlayer(): string {
        return $this->player;
    }

    public static function create(string $player): self {
        return new self($player);
    }
}