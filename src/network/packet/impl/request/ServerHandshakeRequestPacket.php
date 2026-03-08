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

    public static function create(string $serverName, int $processId, int $maxPlayers): self {
        return new self($serverName, $processId, $maxPlayers);
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->serverName, $this->processId, $this->maxPlayers);
    }

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