<?php

namespace pocketcloud\cloud\bridge\form\sub;

use pocketcloud\cloud\bridge\form\sub\module\DisableModuleForm;
use pocketcloud\cloud\bridge\form\sub\module\EnableModuleForm;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\Module;
use pocketcloud\cloud\bridge\module\ModuleManager;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\player\Player;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\element\text\Divider;
use r3pt1s\forms\type\menu\MenuForm;

final class ManageModulesForm extends MenuForm {

    public function __construct() {
        $modules = ModuleManager::getInstance()->getAll();

        $elements = [
            new MenuOption(LanguageKey::INGAME_UI_MANAGE_MODULE_BUTTON_ENABLE(), extraData: ["action" => "enable"]),
            new MenuOption(LanguageKey::INGAME_UI_MANAGE_MODULE_BUTTON_DISABLE(), extraData: ["action" => "disable"]),
            new Divider(),
        ];

        $elements = array_merge(
            $elements,
            array_map(
                fn(Module $m) => new MenuOption(
                    Utils::multiLine(
                        ($m->isEnabled() ? "§a" : "§c") . $m->getName(),
                        "§7" . $m->getDescription()
                    ),
                    extraData: ["moduleName" => $m->getName()]
                ),
                array_values($modules)
            )
        );

        parent::__construct(
            LanguageKey::INGAME_UI_MANAGE_MODULE_TITLE(),
            LanguageKey::INGAME_UI_MANAGE_MODULE_TEXT(),
            $elements
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        $action = $option->get("action");
        if ($action !== null) {
            match ($action) {
                "enable" => $player->sendForm(new EnableModuleForm()),
                "disable" => $player->sendForm(new DisableModuleForm()),
                default => null,
            };
        }
    }
}