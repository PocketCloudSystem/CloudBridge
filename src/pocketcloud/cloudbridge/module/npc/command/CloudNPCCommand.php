<?php

namespace pocketcloud\cloudbridge\module\npc\command;

use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\npc\form\NPCMainForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class CloudNPCCommand extends Command {

    public function __construct() {
        parent::__construct("cloudnpc", Language::current()->translate("inGame.command.description.cloudnpc"), "/cloudnpc", []);
        $this->setPermission("pocketcloud.command.cloudnpc");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($this->testPermissionSilent($sender)) {
                $sender->sendForm(new NPCMainForm());
            } else $sender->sendMessage(Language::current()->translate("inGame.no.permission"));
        }
        return true;
    }
}