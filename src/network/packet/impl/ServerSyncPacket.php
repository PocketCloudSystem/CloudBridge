<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;

final class ServerSyncPacket extends CloudPacket implements ClientboundPacket {

    public function __construct(
        private ?CloudServer $server = null,
        private bool $removal = false
    ) {}

    public function handle(): void {
        if ($this->removal) CloudServerProvider::provider()->remove($this->server);
        else CloudServerProvider::provider()->add($this->server);
    }

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->server, &$this->removal], [fn() => $packetData->readServer(), fn() => $packetData->readBool()]);
    }

    public function getServer(): ?CloudServer {
        return $this->server;
    }

    public function isRemoval(): bool {
        return $this->removal;
    }

    public static function create(CloudServer $server, bool $removal): self {
        return new self($server, $removal);
    }
}