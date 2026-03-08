<?php

namespace pocketcloud\cloud\bridge\form;

use pocketcloud\cloud\bridge\form\sub\ManageModulesForm;
use pocketcloud\cloud\bridge\form\sub\ManagePlayersForm;
use pocketcloud\cloud\bridge\form\sub\ManageServersForm;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\player\Player;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\element\text\Divider;
use r3pt1s\forms\element\text\Header;
use r3pt1s\forms\type\menu\MenuForm;

final class CloudMainForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            LanguageKey::INGAME_UI_CLOUD_MAIN_TITLE(),
            LanguageKey::INGAME_UI_CLOUD_MAIN_TEXT(),
            [
                new Header("§cGeneral"),
                new Divider(),
                new MenuOption(LanguageKey::INGAME_UI_CLOUD_MAIN_BUTTON_MANAGE_SERVER(), extraData: ["action" => "manage_servers"]),
                new MenuOption(LanguageKey::INGAME_UI_CLOUD_MAIN_BUTTON_MANAGE_PLAYER(), extraData: ["action" => "manage_players"]),
                new MenuOption(LanguageKey::INGAME_UI_CLOUD_MAIN_BUTTON_MANAGE_MODULE(), extraData: ["action" => "manage_modules"]),
                new Divider(),
            ]
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        $action = $option->get("action");
        match ($action) {
            "manage_servers" => $player->sendForm(new ManageServersForm()),
            "manage_players" => $player->sendForm(new ManagePlayersForm()),
            "manage_modules" => $player->sendForm(new ManageModulesForm()),
            default => null,
        };
    }
}