<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;

final class PlayerTransferPacket extends CloudPacket implements CloudboundPacket, ClientboundPacket {

    public function __construct(
        private string $player = "",
        private string $server = ""
    ) {}

    public static function create(string $player, string $server): self {
        return new self($player, $server);
    }

    public function handle(): void {
        $player = CloudPlayerProvider::provider()->get($this->player);
        $server = CloudServerProvider::provider()->get($this->server);
        if ($player !== null && $server !== null) {
            CloudPlayerProvider::provider()->transfer($player, $server);
        }
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->player, $this->server);
    }

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAll($this->player, $this->server);
    }

    public function getPlayer(): string {
        return $this->player;
    }

    public function getServer(): string {
        return $this->server;
    }
}