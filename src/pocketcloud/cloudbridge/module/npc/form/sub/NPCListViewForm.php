<?php

namespace pocketcloud\cloudbridge\module\npc\form\sub;

use pocketcloud\cloudbridge\lib\dktapps\pmforms\MenuForm;
use pocketcloud\cloudbridge\lib\dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketmine\player\Player;

class NPCListViewForm extends MenuForm {

    public function __construct(CloudNPC $cloudNPC) {
        $text = "§7Template: §e" . $cloudNPC->getTemplate()->getName();
        $text .= "\n§7Position: §e" . $cloudNPC->getPosition()->getWorld()->getFolderName() . "§8: §e" . $cloudNPC->getPosition()->getX() . "§8, §e" . $cloudNPC->getPosition()->getY() . "§8, §e" . $cloudNPC->getPosition()->getZ();
        $text .= "\n§7Creator: §e" . $cloudNPC->getCreator();
        parent::__construct("§8» §eManage NPCs §8| §eList §8«", $text, [new MenuOption("§cBack")], function(Player $player, int $data): void {
            $player->sendForm(new NPCListForm());
        }, function(Player $player): void {
            $player->sendForm(new NPCListForm());
        });
    }
}