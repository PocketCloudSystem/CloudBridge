<?php

namespace pocketcloud\cloudbridge\module\npc\form\sub;

use pocketcloud\cloudbridge\lib\dktapps\pmforms\MenuForm;
use pocketcloud\cloudbridge\lib\dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketcloud\cloudbridge\module\npc\CloudNPCManager;
use pocketcloud\cloudbridge\module\npc\form\NPCMainForm;
use pocketmine\player\Player;

class NPCListForm extends MenuForm {

    private array $options = [];
    /** @var array<CloudNPC> */
    private array $npcs = [];
    private int $count = 0;

    public function __construct() {
        if (empty(CloudNPCManager::getInstance()->getCloudNPCs())) $this->options[] = new MenuOption("§cThere are no npcs.");

        foreach (CloudNPCManager::getInstance()->getCloudNPCs() as $cloudNPC) {
            $position = $cloudNPC->getPosition();
            $this->options[] = new MenuOption("§e" . $cloudNPC->getTemplate()->getName() . "\n§7" . $position->getWorld()->getFolderName() . "§8: §7" . $position->getX() . "§8, §7" . $position->getY() . "§8, §7" . $position->getZ());
            $this->npcs[] = $cloudNPC;
            $this->count++;
        }

        parent::__construct("§8» §eManage NPCs §8| §eList §8«", "§7There are currently §e" . $this->count . " " . ($this->count == 1 ? "NPC" : "NPCs") . " §7available.", $this->options, function(Player $player, int $data): void {
            if ($this->count == 0) {
                $player->sendForm(new self());
                return;
            }

            $cloudNPC = $this->npcs[$data] ?? null;
            if ($cloudNPC !== null) {
                $player->sendForm(new NPCListViewForm($cloudNPC));
            }
        }, function(Player $player): void {
            $player->sendForm(new NPCMainForm());
        });
    }
}