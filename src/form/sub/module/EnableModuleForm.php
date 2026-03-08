<?php

namespace pocketcloud\cloud\bridge\form\sub\module;

use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\Module;
use pocketcloud\cloud\bridge\module\ModuleManager;
use pocketmine\player\Player;
use r3pt1s\forms\element\custom\Dropdown;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\misc\CustomFormResponse;

final class EnableModuleForm extends CustomForm {

    /** @var array<Module> */
    private array $modules;

    public function __construct() {
        $this->modules = array_values(ModuleManager::getInstance()->getDisabledModules());
        parent::__construct(
            LanguageKey::INGAME_UI_MANAGE_MODULE_SUB_ENABLE_TITLE(),
            [
                new Dropdown(
                    "module",
                    LanguageKey::INGAME_UI_MANAGE_MODULE_SUB_ENABLE_DROPDOWN_TEXT(),
                    array_map(fn(Module $m) => $m->getName(), $this->modules)
                )
            ]
        );
    }

    public function onSubmit(Player $player, CustomFormResponse $response): void {
        $module = $this->modules[$response->getInt("module")] ?? null;
        if ($module === null) return;

        if ($module->isEnabled()) {
            $player->sendMessage(LanguageKey::INGAME_MODULE_ALREADY_ENABLED()->translate([$module->getName()]));
            return;
        }

        ModuleManager::getInstance()->enable($module);
        $player->sendMessage(LanguageKey::INGAME_MODULE_ENABLED()->translate([$module->getName()]));
    }
}