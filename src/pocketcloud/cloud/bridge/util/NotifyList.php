<?php

namespace pocketcloud\cloud\bridge\util;

use pocketmine\player\Player;

final class NotifyList {

    private static array $list = [];

    public static function put(Player $player): void {
        self::$list[$player->getName()] = true;
    }

    public static function remove(Player $player): void {
        if (self::exists($player)) unset(self::$list[$player->getName()]);
    }

    public static function exists(Player $player): bool {
        return isset(self::$list[$player->getName()]);
    }
}