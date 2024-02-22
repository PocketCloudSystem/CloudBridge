<?php

namespace pocketcloud\cloudbridge\module\npc\form\sub;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\npc\CloudNPCModule;
use pocketcloud\cloudbridge\util\Utils;
use pocketmine\player\Player;

class NPCListForm extends MenuForm {

    public function __construct() {
        $npcs = CloudNPCModule::get()->getCloudNPCs();
        parent::__construct(
            Language::current()->translate("inGame.ui.cloudnpc.list.title"),
            Language::current()->translate("inGame.ui.cloudnpc.list.text", count($npcs)),
            array_map(fn(CloudNPC $npc) => new MenuOption("§e" . $npc->getTemplate()->getName() . "\n§a" . Utils::convertToString($npc->getPosition())), $npcs),
            function(Player $player, int $data) use($npcs): void {
                if (empty($npcs)) return;

                $cloudNPC = $this->npcs[$data] ?? null;
                if ($cloudNPC !== null) {
                    $player->sendForm(new NPCListViewForm($cloudNPC));
                }
            }
        );
    }
}