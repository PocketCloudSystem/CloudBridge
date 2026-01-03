<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;
use pocketmine\Server;

final class PlayerKickPacket extends CloudPacket implements ClientboundPacket, CloudboundPacket {

    public function __construct(
        private string $player = "",
        private string $reason = "",
        private string $disconnectScreenMessage = ""
    ) {}

    public function handle(): void {
        if (($player = Server::getInstance()->getPlayerExact($this->player)) !== null) $player->kick($this->reason, "", $this->disconnectScreenMessage !== "" ? $this->disconnectScreenMessage : $this->reason);
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->player, $this->reason, $this->disconnectScreenMessage);
    }

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAll($this->player, $this->reason, $this->disconnectScreenMessage);
    }

    public static function create(string $player, string $reason, string $disconnectScreenMessage): self {
        return new self($player, $reason, $disconnectScreenMessage);
    }
}