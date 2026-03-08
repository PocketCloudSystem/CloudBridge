<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\command;

use pocketcloud\cloud\bridge\command\BaseCloudCommand;
use pocketcloud\cloud\bridge\module\impl\npc\form\NPCMainForm;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class CloudNPCCommand extends BaseCloudCommand {

    public function __construct() {
        parent::__construct("cloudnpc", LanguageKey::INGAME_COMMAND_DESCRIPTION_CLOUDNPC());
        $this->setPermission("pocketcloud.command.cloudnpc");
    }

    public function run(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($this->testPermissionSilent($sender)) {
                $sender->sendForm(new NPCMainForm());
            } else $sender->sendMessage(LanguageKey::INGAME_NO_PERMISSION());
        }
        return true;
    }
}