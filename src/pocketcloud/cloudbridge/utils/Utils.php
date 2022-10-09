<?php

namespace pocketcloud\cloudbridge\utils;

use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\Position;

class Utils {

    public static function containKeys(array $array, ...$keys): bool {
        $result = true;
        foreach ($keys as $key) {
            if (!isset($array[$key])) $result = false;
        }
        return $result;
    }

    public static function convertToString(Vector3 $vector): string {
        return match (get_class($vector)) {
            Position::class => (int)$vector->getX() . ":" . (int)$vector->getY() . ":" . (int)$vector->getZ() . ":" . $vector->getWorld()->getFolderName(),
            Location::class => (int)$vector->getX() . ":" . (int)$vector->getY() . ":" . (int)$vector->getZ() . ":" . $vector->getWorld()?->getFolderName() . ":" . $vector->getYaw() . ":" . $vector->getPitch(),
            default => (int)$vector->getX() . ":" . (int)$vector->getY() . ":" . (int)$vector->getZ()
        };
    }

    public static function convertToVector(string $string): ?Vector3 {
        $world = null;
        if (($count = count(($explode = explode(":", $string)))) > 3) $world = Server::getInstance()->getWorldManager()->getWorldByName($explode[3]);
        return match ($count) {
            3 => new Vector3(intval($explode[0]), intval($explode[1]), intval($explode[2])),
            4 => new Position(intval($explode[0]), intval($explode[1]), intval($explode[2]), $world),
            5 => new Location(intval($explode[0]), intval($explode[1]), intval($explode[2]), $world, floatval($explode[4]), floatval($explode[5])),
            default => null
        };
    }
}