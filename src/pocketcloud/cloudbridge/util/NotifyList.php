<?php

namespace pocketcloud\cloudbridge\util;

use pocketmine\player\Player;

class NotifyList {

    private static array $list = [];

    public static function put(Player $player) {
        self::$list[$player->getName()] = true;
    }

    public static function remove(Player $player) {
        if (self::exists($player)) unset(self::$list[$player->getName()]);
    }

    public static function exists(Player $player): bool {
        return isset(self::$list[$player->getName()]);
    }
}