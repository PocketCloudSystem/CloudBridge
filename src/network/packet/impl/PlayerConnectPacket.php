<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class PlayerConnectPacket extends CloudPacket implements CloudboundPacket {

    public function __construct(private readonly ?CloudPlayer $player = null) {}

    public function handle(): void {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->player);
    }

    public function decodePayload(PacketData $packetData): void {}

    public function getPlayer(): ?CloudPlayer {
        return $this->player;
    }

    public static function create(CloudPlayer $player): self {
        return new self($player);
    }
}