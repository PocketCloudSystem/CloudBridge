<?php

namespace pocketcloud\cloud\bridge\player;

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use WeakMap;

final class PlayerSessionManager {
    use SingletonTrait;

    /** @var WeakMap<Player, PlayerSession> */
    private WeakMap $sessions;

    public function __construct() {
        self::setInstance($this);
        $this->sessions = new WeakMap();
    }

    public function tick(): void {
        foreach ($this->sessions as $session) $session->tick();
    }

    public function get(Player $player): PlayerSession {
        if (!isset($this->sessions[$player])) $this->create($player);
        return $this->sessions[$player];
    }

    public function create(Player $player): void {
        $this->sessions[$player] = new PlayerSession($player);
    }

    public function getAll(): WeakMap {
        return $this->sessions;
    }
}