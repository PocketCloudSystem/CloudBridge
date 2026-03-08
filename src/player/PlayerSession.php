<?php

namespace pocketcloud\cloud\bridge\player;

use pocketmine\player\Player;
use pocketmine\Server;

final readonly class PlayerSession {

    public function __construct(private string $name) {}

    public static function get(Player $player): PlayerSession {
        return PlayerSessionManager::getInstance()->get($player);
    }

    public function tick(): void {}

    public function getPlayer(): ?Player {
        return Server::getInstance()->getPlayerExact($this->name);
    }

    public function getPlayerName(): string {
        return $this->name;
    }
}