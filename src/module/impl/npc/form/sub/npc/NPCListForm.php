<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\form\sub\npc;

use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPC;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\player\Player;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\type\menu\MenuForm;

final class NPCListForm extends MenuForm {

    /** @var array<CloudNPC> */
    private array $npcs;

    public function __construct() {
        $this->npcs = array_values(CloudNPCModule::get()->getAll());
        parent::__construct(
            LanguageKey::INGAME_UI_CLOUDNPC_LIST_TITLE(),
            LanguageKey::INGAME_UI_CLOUDNPC_LIST_TEXT()->translate([count($this->npcs)]),
            array_map(
                fn(CloudNPC $npc) => new MenuOption(
                    "§e" .
                    ($npc->hasTemplateGroup() ? $npc->getTemplate()->getDisplayName() : $npc->getTemplate()->getName()) .
                    "\n§e" .
                    str_replace(":", "§8:§e", Utils::convertToString($npc->getPosition()))
                ),
                $this->npcs
            )
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        if (empty($this->npcs)) return;
        $npc = $this->npcs[$index] ?? null;
        if ($npc !== null) {
            $player->sendForm(new NPCListViewForm($npc));
        }
    }
}