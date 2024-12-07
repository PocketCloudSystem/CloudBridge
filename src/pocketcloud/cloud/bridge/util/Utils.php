<?php

namespace pocketcloud\cloud\bridge\util;

use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\Position;

class Utils {

    public static function containKeys(array $array, ...$keys): bool {
        foreach ($keys as $key) {
            if (!isset($array[$key])) return false;
        }

        return true;
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

    public static function fromImage($pathOrImage): string {
        if (is_string($pathOrImage)) $pathOrImage = imagecreatefrompng($pathOrImage);
        $bytes = "";
        for ($y = 0; $y < imagesy($pathOrImage); $y++) {
            for ($x = 0; $x < imagesx($pathOrImage); $x++) {
                $rgba = @imagecolorat($pathOrImage, $x, $y);
                $a = ((~($rgba >> 24)) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($pathOrImage);
        return $bytes;
    }
}