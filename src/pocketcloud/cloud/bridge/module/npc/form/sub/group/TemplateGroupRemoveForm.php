<?php

namespace pocketcloud\cloud\bridge\module\npc\form\sub\group;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\module\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\npc\group\TemplateGroup;
use pocketmine\player\Player;

final class TemplateGroupRemoveForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.template_group.remove.title"),
            Language::current()->translate("inGame.ui.template_group.remove.text"),
            array_map(fn(TemplateGroup $group) => new MenuOption($group->getDisplayName() . "\n§r§e" . $group->getId()), $groups = array_values(CloudNPCModule::get()->getTemplateGroups())),
            function (Player $player, int $data) use($groups): void {
                $group = $groups[$data] ?? null;
                if ($group !== null) {
                    if (CloudNPCModule::get()->removeTemplateGroup($group)) {
                        $player->sendMessage(Language::current()->translate("inGame.template_group.removed", $group->getId()));
                    } else $player->sendMessage(CloudBridge::getPrefix() . "§cAn error occurred while removing the group: §e" . $group->getId() . "§c. Please report that incident on our discord.");
                }
            }
        );
    }
}