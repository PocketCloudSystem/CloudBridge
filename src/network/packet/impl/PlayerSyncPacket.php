<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class PlayerSyncPacket extends CloudPacket implements ClientboundPacket {

    public function __construct(
        private ?CloudPlayer $player = null,
        private bool $removal = false
    ) {}

    public static function create(CloudPlayer $player, bool $removal): self {
        return new self($player, $removal);
    }

    public function handle(): void {
        if ($this->removal) CloudPlayerProvider::provider()->remove($this->player);
        else CloudPlayerProvider::provider()->add($this->player);
    }

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->player, &$this->removal], [fn() => $packetData->readPlayer(), fn() => $packetData->readBool()]);
    }

    public function getPlayer(): ?CloudPlayer {
        return $this->player;
    }

    public function isRemoval(): bool {
        return $this->removal;
    }
}