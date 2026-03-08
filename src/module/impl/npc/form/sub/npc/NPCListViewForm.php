<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\form\sub\npc;

use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPC;
use pocketmine\player\Player;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\type\menu\MenuForm;

final class NPCListViewForm extends MenuForm {

    public function __construct(private readonly CloudNPC $cloudNPC) {
        if ($this->cloudNPC->hasTemplateGroup()) {
            $name = $this->cloudNPC->getTemplate()->getDisplayName();
            $text = "§7TemplateGroup: §e" . $name . " §8(§e" . $this->cloudNPC->getTemplate()->getId() . "§8)";
        } else {
            $name = $this->cloudNPC->getTemplate()->getName();
            $text = "§7Template: §e" . $name;
        }

        $pos = $this->cloudNPC->getPosition();
        $text .= "\n§7Position: §e" . $pos->getWorld()->getFolderName()
            . "§8: §e" . $pos->getX() . "§8, §e" . $pos->getY() . "§8, §e" . $pos->getZ();
        $text .= "\n§7Creator: §e" . $this->cloudNPC->getCreator();

        parent::__construct(
            LanguageKey::INGAME_UI_CLOUDNPC_LIST_VIEW_TITLE()->translate([$name]),
            $text,
            [
                new MenuOption(LanguageKey::INGAME_UI_CLOUDNPC_LIST_VIEW_BUTTON_TELEPORT()),
                new MenuOption(LanguageKey::INGAME_UI_CLOUDNPC_LIST_VIEW_BUTTON_BACK())
            ]
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        if ($index === 0) {
            $player->teleport($this->cloudNPC->getPosition());
        } else {
            $player->sendForm(new NPCListForm());
        }
    }
}