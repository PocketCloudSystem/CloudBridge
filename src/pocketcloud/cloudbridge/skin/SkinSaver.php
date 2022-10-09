<?php

namespace pocketcloud\cloudbridge\skin;

use pocketcloud\cloudbridge\CloudBridge;
use pocketmine\entity\Skin;
use pocketmine\player\Player;

class SkinSaver {

    /** @var array<Skin> */
    private static array $savedSkins = [];

    public static function save(Player $player) {
        if (file_exists(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player->getName() . "_id.txt")) @unlink(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player->getName() . "_id.txt");
        if (file_exists(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player->getName() . "_data.txt")) @unlink(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player->getName() . "_data.txt");
        if (file_exists(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player->getName() . "_cape-data.txt")) @unlink(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player->getName() . "_cape-data.txt");

        file_put_contents(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player->getName() . "_id.txt", $player->getSkin()->getSkinId());
        file_put_contents(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player->getName() . "_data.txt", $player->getSkin()->getSkinData());
        file_put_contents(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player->getName() . "_cape-data.txt", $player->getSkin()->getCapeData());
        if (!isset(self::$savedSkins[$player->getName()])) self::$savedSkins[$player->getName()] = $player->getSkin();
    }

    public static function get(string $player): ?Skin {
        if (isset(self::$savedSkins[$player])) if (($savedSkin = self::$savedSkins[$player]) instanceof Skin) return $savedSkin;
        if (!file_exists(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player . "_id.txt")) return null;
        if (!file_exists(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player . "_data.txt")) return null;
        if (!file_exists(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player . "_cape-data.txt")) return null;
        $skin = new Skin(
            file_get_contents(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player . "_id.txt"),
            file_get_contents(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player . "_data.txt"),
            file_get_contents(CloudBridge::getInstance()->getDataFolder() . "skins/" . $player . "_cape-data.txt")
        );
        self::$savedSkins[$player] = $skin;
        return $skin;
    }
}