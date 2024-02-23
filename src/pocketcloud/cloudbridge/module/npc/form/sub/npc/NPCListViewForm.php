<?php

namespace pocketcloud\cloudbridge\module\npc\form\sub\npc;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketcloud\cloudbridge\module\npc\group\TemplateGroup;
use pocketmine\player\Player;

class NPCListViewForm extends MenuForm {

    public function __construct(private readonly CloudNPC $cloudNPC) {
        if ($this->cloudNPC->hasTemplateGroup()) $text = "§7TemplateGroup: §e" . ($name = $this->cloudNPC->getTemplate()->getDisplayName()) . " §8(§e" . $this->cloudNPC->getTemplate()->getId() . "§8)";
        else $text = "§7Template: §e" . ($name = $this->cloudNPC->getTemplate()->getName());
        $text .= "\n§7Position: §e" . $this->cloudNPC->getPosition()->getWorld()->getFolderName() . "§8: §e" . $this->cloudNPC->getPosition()->getX() . "§8, §e" . $this->cloudNPC->getPosition()->getY() . "§8, §e" . $this->cloudNPC->getPosition()->getZ();
        $text .= "\n§7Creator: §e" . $this->cloudNPC->getCreator();
        parent::__construct(
            Language::current()->translate("inGame.ui.cloudnpc.list_view.title", $name),
            $text,
            [
                new MenuOption(Language::current()->translate("inGame.ui.cloudnpc.list_view.button.teleport")),
                new MenuOption(Language::current()->translate("inGame.ui.cloudnpc.list_view.button.back"))
            ],
            function(Player $player, int $data): void {
                if ($data == 0) {
                    $player->teleport($this->cloudNPC->getPosition());
                } else {
                    $player->sendForm(new NPCListForm());
                }
            }
        );
    }
}