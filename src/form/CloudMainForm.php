<?php

namespace pocketcloud\cloud\bridge\form;

use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\player\Player;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\element\text\Divider;
use r3pt1s\forms\element\text\Header;
use r3pt1s\forms\type\menu\MenuForm;

final class CloudMainForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            LanguageKey::INGAME_UI_CLOUD_MAIN_TITLE()->translate(),
            "",
            [
                new Header("§cGeneral"),
                new Divider(),
                new MenuOption("§eManage Servers"),
                new MenuOption("§gManage Players"),
                new MenuOption("§6Manage Templates"),
                new MenuOption("§bManage ServerGroups"),
                new MenuOption("§dManage Modules"),
                new Divider(),
                new Header("§cMonitoring"),
                new Divider(),
                new MenuOption("§eMonitor Traffic"),
                new Divider(),
                new Header("§cMisc"),
                new Divider(),
                new MenuOption("§6Message to §bCloud §6console")
            ]
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {

    }

    public function onClose(Player $player): void {

    }
}