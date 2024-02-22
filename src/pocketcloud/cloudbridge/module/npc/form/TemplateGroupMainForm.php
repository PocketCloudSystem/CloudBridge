<?php

namespace pocketcloud\cloudbridge\module\npc\form;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\npc\CloudNPCModule;
use pocketcloud\cloudbridge\module\npc\form\sub\group\TemplateGroupCreateForm;
use pocketcloud\cloudbridge\module\npc\form\sub\group\TemplateGroupEditForm;
use pocketcloud\cloudbridge\module\npc\form\sub\group\TemplateGroupRemoveForm;
use pocketcloud\cloudbridge\module\npc\group\TemplateGroup;
use pocketmine\player\Player;

class TemplateGroupMainForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.template_group.main.title"),
            Language::current()->translate("inGame.ui.template_group.main.text"),
            [
                new MenuOption(Language::current()->translate("inGame.ui.template_group.main.button.create")),
                new MenuOption(Language::current()->translate("inGame.ui.template_group.main.button.edit")),
                new MenuOption(Language::current()->translate("inGame.ui.template_group.main.button.remove")),
                new MenuOption(Language::current()->translate("inGame.ui.template_group.main.button.list"))
            ],
            function(Player $player, int $data): void {
                if ($data == 0) {
                    $player->sendForm(new TemplateGroupCreateForm());
                } else if ($data == 1) {
                    $player->sendForm(new MenuForm(
                        Language::current()->translate("inGame.ui.template_group.edit_selection.title"),
                        Language::current()->translate("inGame.ui.template_group.edit_selection.text"),
                        array_map(fn(TemplateGroup $templateGroup) => new MenuOption($templateGroup->getDisplayName() . "\n§r§e" . $templateGroup->getId()), $groups = array_values(CloudNPCModule::get()->getTemplateGroups())),
                        function (Player $player, int $data) use($groups): void {
                            $group = $groups[$data] ?? null;
                            if ($group !== null) {
                                $player->sendForm(new TemplateGroupEditForm($group));
                            }
                        }
                    ));
                } else if ($data == 2) {
                    $player->sendForm(new TemplateGroupRemoveForm());
                } else if ($data == 3) {
                    $player->sendMessage(CloudBridge::getPrefix() . "§7Groups: §8(§e" . count(CloudNPCModule::get()->getTemplateGroups()) . "§8)§7:");
                    if (empty(CloudNPCModule::get()->getTemplateGroups())) $player->sendMessage(CloudBridge::getPrefix() . "§7No groups available.");
                    foreach (CloudNPCModule::get()->getTemplateGroups() as $group) {
                        $player->sendMessage(
                            CloudBridge::getPrefix() . "§e" . $group->getId() .
                            " §8- §7Display: §e" . $player->getDisplayName() .
                            " §r§8- §7Templates: §e" . (empty($group->getTemplates()) ? "§c/" : implode("§8, §e", $group->getTemplates()))
                        );
                    }
                }
            }
        );
    }
}