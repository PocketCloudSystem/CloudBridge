<?php

namespace pocketcloud\cloud\bridge\api\provider;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;

final class CloudPlayerProvider implements CloudAPIProvider {
    use CloudAPIGetProviderTrait;

    /** @var array<CloudPlayer> */
    private array $players = [];

    public function add(CloudPlayer $player): void {
        $this->players[$player->getName()] = $player;
    }

    public function remove(CloudPlayer $player): void {
        if ($this->isset($player)) unset($this->players[$player->getName()]);
    }

    public function isset(CloudPlayer|string $name): bool {
        $name = $name instanceof CloudPlayer ? $name->getName() : $name;
        return isset($this->players[$name]);
    }

    public function get(string $name): ?CloudPlayer {
        return $this->players[$name] ?? null;
    }

    public function current(): CloudPlayer {
        return $this->get(CloudEnvironmentConfig::getServerName());
    }

    public function getAll(): array {
        return $this->players;
    }
}