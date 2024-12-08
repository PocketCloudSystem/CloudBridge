<?php

namespace pocketcloud\cloud\bridge\module\npc\form;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\module\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\npc\form\sub\npc\NPCCreateForm;
use pocketcloud\cloud\bridge\module\npc\form\sub\npc\NPCListForm;
use pocketmine\player\Player;

final class NPCMainForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.cloudnpc.main.title"),
            Language::current()->translate("inGame.ui.cloudnpc.main.text"),
            [
                new MenuOption(Language::current()->translate("inGame.ui.cloudnpc.main.button.create")),
                new MenuOption(Language::current()->translate("inGame.ui.cloudnpc.main.button.remove")),
                new MenuOption(Language::current()->translate("inGame.ui.cloudnpc.main.button.list")),
                new MenuOption(Language::current()->translate("inGame.ui.cloudnpc.main.button.models"))
            ],
            function(Player $player, int $data): void {
                if ($data == 0) {
                    $player->sendForm(new NPCCreateForm());
                } else if ($data == 1) {
                    if (isset(CloudNPCModule::get()->npcDetection[$player->getName()])) {
                        $player->sendMessage(Language::current()->translate("inGame.cloudnpc.process.cancelled"));
                        unset(CloudNPCModule::get()->npcDetection[$player->getName()]);
                    } else {
                        $player->sendMessage(Language::current()->translate("inGame.cloudnpc.select"));
                        CloudNPCModule::get()->npcDetection[$player->getName()] = $player->getName();
                    }
                } else if ($data == 2) {
                    $player->sendForm(new NPCListForm());
                } else if ($data == 3) {
                    $player->sendForm(new SkinModelMainForm());
                }
            }
        );
    }
}