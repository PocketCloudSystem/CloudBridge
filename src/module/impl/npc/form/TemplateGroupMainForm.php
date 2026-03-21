<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\form;

use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\impl\npc\form\sub\group\TemplateGroupCreateForm;
use pocketcloud\cloud\bridge\module\impl\npc\form\sub\group\TemplateGroupEditForm;
use pocketcloud\cloud\bridge\module\impl\npc\form\sub\group\TemplateGroupRemoveForm;
use pocketcloud\cloud\bridge\module\impl\npc\group\TemplateGroup;
use pocketmine\player\Player;
use r3pt1s\forms\builder\MenuFormBuilder;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\type\menu\MenuForm;

final class TemplateGroupMainForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            LanguageKey::INGAME_UI_TEMPLATE_GROUP_MAIN_TITLE(),
            LanguageKey::INGAME_UI_TEMPLATE_GROUP_MAIN_TEXT(),
            [
                new MenuOption(LanguageKey::INGAME_UI_TEMPLATE_GROUP_MAIN_BUTTON_CREATE()),
                new MenuOption(LanguageKey::INGAME_UI_TEMPLATE_GROUP_MAIN_BUTTON_EDIT()),
                new MenuOption(LanguageKey::INGAME_UI_TEMPLATE_GROUP_MAIN_BUTTON_REMOVE()),
                new MenuOption(LanguageKey::INGAME_UI_TEMPLATE_GROUP_MAIN_BUTTON_LIST())
            ]
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        if ($index === 0) {
            $player->sendForm(new TemplateGroupCreateForm());
        } elseif ($index === 1) {
            $groups = array_values(CloudNPCModule::get()->getTemplateGroups());
            if (empty($groups)) {
                $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§7No groups available.");
                return;
            }

            $player->sendForm(MenuFormBuilder::create(LanguageKey::INGAME_UI_TEMPLATE_GROUP_EDIT_SELECTION_TITLE(), LanguageKey::INGAME_UI_TEMPLATE_GROUP_EDIT_SELECTION_TEXT())
                ->elements(array_map(fn(TemplateGroup $g) => new MenuOption($g->getDisplayName() . "\n§r§e" . $g->getId()), $groups))
                ->onSubmit(function (Player $player, int $index) use($groups): void {
                    $group = $this->groups[$index] ?? null;
                    if ($group !== null) {
                        $player->sendForm(new TemplateGroupEditForm($group));
                    }
                })
                ->build()
            );
        } elseif ($index === 2) {
            if (empty(CloudNPCModule::get()->getTemplateGroups())) {
                $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§7No groups available.");
                return;
            }
            $player->sendForm(new TemplateGroupRemoveForm());
        } elseif ($index === 3) {
            $groups = CloudNPCModule::get()->getTemplateGroups();
            $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§7Groups: §8(§e" . count($groups) . "§8)§7:");
            if (empty($groups)) {
                $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§7No groups available.");
                return;
            }

            foreach ($groups as $group) {
                $player->sendMessage(
                    LanguageKey::INGAME_PREFIX() .
                    "§e" .
                    $group->getId() .
                    " §8- §7Display: §e" .
                    $group->getDisplayName() .
                    " §r§8- §7Templates: §e" .
                    (empty($group->getTemplates()) ? "§c/" : implode("§8, §e", $group->getTemplates()))
                );
            }
        }
    }
}