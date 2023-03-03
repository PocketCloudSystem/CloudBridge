<?php

namespace pocketcloud\cloudbridge\module\npc\form;

use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\lib\dktapps\pmforms\MenuForm;
use pocketcloud\cloudbridge\lib\dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\module\npc\form\sub\NPCCreateForm;
use pocketcloud\cloudbridge\module\npc\form\sub\NPCListForm;
use pocketcloud\cloudbridge\utils\Message;
use pocketmine\player\Player;

class NPCMainForm extends MenuForm {

    private array $options;

    public function __construct() {
        $this->options = [
            new MenuOption("§aCreate a NPC"),
            new MenuOption("§cRemove a NPC"),
            new MenuOption("§eList all NPCs")
        ];

        parent::__construct("§8» §eManage NPCs §8«", "", $this->options, function(Player $player, int $data): void {
            if ($data == 0) {
                $player->sendForm(new NPCCreateForm());
            } else if ($data == 1) {
                if (isset(CloudBridge::getInstance()->npcDetection[$player->getName()])) {
                    Message::parse(Message::PROCESS_CANCELLED)->target($player);
                    unset(CloudBridge::getInstance()->npcDetection[$player->getName()]);
                } else {
                    Message::parse(Message::SELECT_NPC)->target($player);
                    CloudBridge::getInstance()->npcDetection[$player->getName()] = $player->getName();
                }
            } else if ($data == 2) {
                $player->sendForm(new NPCListForm());
            }
        });
    }
}