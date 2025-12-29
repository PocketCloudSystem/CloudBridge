<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\request;

use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class ServerHandshakeRequestPacket extends RequestPacket {

    public function __construct(
        private readonly ?string $serverName = null,
        private readonly ?int $processId = null,
        private readonly ?int $maxPlayers = null
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->serverName)
            ->write($this->processId)
            ->write($this->maxPlayers);
    }

    public function decodePayload(PacketData $packetData): void {}

    public function getServerName(): ?string {
        return $this->serverName;
    }

    public function getProcessId(): ?int {
        return $this->processId;
    }

    public function getMaxPlayers(): ?int {
        return $this->maxPlayers;
    }
}