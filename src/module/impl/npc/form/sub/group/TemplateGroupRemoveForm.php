<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\form\sub\group;

use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\impl\npc\group\TemplateGroup;
use pocketmine\player\Player;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\type\menu\MenuForm;

final class TemplateGroupRemoveForm extends MenuForm {

    /** @var array<TemplateGroup> */
    private array $groups;

    public function __construct() {
        $this->groups = array_values(CloudNPCModule::get()->getTemplateGroups());
        parent::__construct(
            LanguageKey::INGAME_UI_TEMPLATE_GROUP_REMOVE_TITLE(),
            LanguageKey::INGAME_UI_TEMPLATE_GROUP_REMOVE_TEXT(),
            array_map(
                fn(TemplateGroup $g) => new MenuOption($g->getDisplayName() . "\n§r§e" . $g->getId()),
                $this->groups
            )
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        $group = $this->groups[$index] ?? null;
        if ($group === null) return;

        if (CloudNPCModule::get()->removeTemplateGroup($group)) {
            $player->sendMessage(LanguageKey::INGAME_TEMPLATE_GROUP_REMOVED()->translate([$group->getId()]));
        } else {
            $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§cAn error occurred while removing the group: §e" . $group->getId());
        }
    }
}