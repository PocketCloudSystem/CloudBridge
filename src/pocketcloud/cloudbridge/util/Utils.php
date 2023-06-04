<?php

namespace pocketcloud\cloudbridge\util;

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
        return match ($vector::class) {
            Location::class => $vector->getX() . ":" . $vector->getY() . ":" . $vector->getZ() . ":" . $vector->getWorld()->getFolderName() . ":" . $vector->getYaw() . ":" . $vector->getPitch(),
            Position::class => $vector->getX() . ":" . $vector->getY() . ":" . $vector->getZ() . ":" . $vector->getWorld()->getFolderName(),
            default => $vector->getX() . ":" . $vector->getY() . ":" . $vector->getZ()
        };
    }

    public static function convertToVector(string $string): Vector3 {
        $explode = explode(":", $string);
        return match (count($explode)) {
            6 => new Location(floatval($explode[0]), floatval($explode[1]), floatval($explode[2]), Server::getInstance()->getWorldManager()->getWorldByName($explode[3]), floatval($explode[4]), floatval($explode[5])),
            4 => new Position(floatval($explode[0]), floatval($explode[1]), floatval($explode[2]), Server::getInstance()->getWorldManager()->getWorldByName($explode[3])),
            3 => new Vector3(floatval($explode[0]), floatval($explode[1]), floatval($explode[2])),
            default => Vector3::zero()
        };
    }
}