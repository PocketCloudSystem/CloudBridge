<?php

namespace pocketcloud\cloud\bridge\server\data;

final readonly class CloudServerData {

    public function __construct(
        private string $serverName,
        private int $port,
        private int $maxPlayers,
        private ?int $processId
    ) {}

    public function getServerName(): string {
        return $this->serverName;
    }

    public function getPort(): int {
        return $this->port;
    }

    public function getMaxPlayers(): int {
        return $this->maxPlayers;
    }

    public function getProcessId(): ?int {
        return $this->processId;
    }
}