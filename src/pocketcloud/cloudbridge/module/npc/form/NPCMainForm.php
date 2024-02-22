<?php

namespace pocketcloud\cloudbridge\module\npc\form;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\npc\CloudNPCModule;
use pocketcloud\cloudbridge\module\npc\form\sub\npc\NPCCreateForm;
use pocketcloud\cloudbridge\module\npc\form\sub\npc\NPCListForm;
use pocketmine\player\Player;

class NPCMainForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.cloudnpc.main.title"),
            Language::current()->translate("inGame.ui.cloudnpc.main.text"),
            [
                new MenuOption(Language::current()->translate("inGame.ui.cloudnpc.main.button.create")),
                new MenuOption(Language::current()->translate("inGame.ui.cloudnpc.main.button.remove")),
                new MenuOption(Language::current()->translate("inGame.ui.cloudnpc.main.button.list"))
            ],
            function(Player $player, int $data): void {
                if ($data == 0) {
                    $player->sendForm(new NPCCreateForm());
                } else if ($data == 1) {
                    if (isset(CloudBridge::getInstance()->npcDetection[$player->getName()])) {
                        $player->sendMessage(Language::current()->translate("inGame.cloudnpc.process.cancelled"));
                        unset(CloudBridge::getInstance()->npcDetection[$player->getName()]);
                    } else {
                        $player->sendMessage(Language::current()->translate("inGame.cloudnpc.select"));
                        CloudNPCModule::get()->npcDetection[$player->getName()] = $player->getName();
                    }
                } else if ($data == 2) {
                    $player->sendForm(new NPCListForm());
                }
            }
        );
    }
}