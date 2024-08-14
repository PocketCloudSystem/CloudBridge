<?php

namespace pocketcloud\cloudbridge\form\sub;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\module\hubcommand\HubCommandModule;
use pocketcloud\cloudbridge\module\npc\CloudNPCModule;
use pocketcloud\cloudbridge\form\selection\CloudSelectionForm;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\sign\CloudSignModule;
use pocketmine\player\Player;

class CloudManageModulesSubForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.manage_module.title"),
            Language::current()->translate("inGame.ui.manage_module.text"),
            [
                new MenuOption(Language::current()->translate("inGame.ui.manage_module.button.enable")),
                new MenuOption(Language::current()->translate("inGame.ui.manage_module.button.disable")),
                new MenuOption(Language::current()->translate("inGame.ui.manage_module.button.list"))
            ],
            function(Player $player, int $data): void {
                if ($data == 0) {
                    if (empty($this->disabledModules())) {
                        $player->sendMessage(Language::current()->translate("inGame.module.no.disabled"));
                        return;
                    }

                    $player->sendForm(new CloudSelectionForm(
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_module.sub.enable.title"),
                            [new Input("name", Language::current()->translate("inGame.ui.manage_module.sub.enable.name.text"))],
                            function(Player $player, CustomFormResponse $response): void {
                                $player->chat("/cloud enable " . $response->getString("name"));
                            }
                        ),
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_module.sub.enable.title"),
                            [new Dropdown("name", Language::current()->translate("inGame.ui.manage_module.sub.enable.dropdown.text"), $this->disabledModules())],
                            function(Player $player, CustomFormResponse $response): void {
                                $module = $this->disabledModules()[$response->getInt("name")] ?? null;
                                if ($module !== null) $player->chat("/cloud enable " . $module);
                            }
                        )
                    ));
                } else if ($data == 1) {
                    if (empty($this->enabledModules())) {
                        $player->sendMessage(Language::current()->translate("inGame.module.no.enabled"));
                        return;
                    }

                    $player->sendForm(new CloudSelectionForm(
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_module.sub.disable.title"),
                            [new Input("name", Language::current()->translate("inGame.ui.manage_module.sub.disable.name.text"))],
                            function(Player $player, CustomFormResponse $response): void {
                                $player->chat("/cloud disable " . $response->getString("name"));
                            }
                        ),
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_module.sub.disable.title"),
                            [new Dropdown("name", Language::current()->translate("inGame.ui.manage_module.sub.disable.dropdown.text"), $this->enabledModules())],
                            function(Player $player, CustomFormResponse $response): void {
                                $module = $this->enabledModules()[$response->getInt("name")] ?? null;
                                if ($module !== null) $player->chat("/cloud disable " . $module);
                            }
                        )
                    ));
                } else if ($data == 2) {
                    $player->chat("/cloud list modules");
                }
            }
        );
    }

    private function enabledModules(): array {
        $modules = [];
        if (CloudSignModule::get()->isEnabled()) $modules[] = "signmodule";
        if (CloudNPCModule::get()->isEnabled()) $modules[] = "npcmodule";
        if (HubCommandModule::get()->isEnabled()) $modules[] = "hubcommand";
        return $modules;
    }

    private function disabledModules(): array {
        $modules = [];
        if (!CloudSignModule::get()->isEnabled()) $modules[] = "signmodule";
        if (!CloudNPCModule::get()->isEnabled()) $modules[] = "npcmodule";
        if (!HubCommandModule::get()->isEnabled()) $modules[] = "hubcommand";
        return $modules;
    }
}