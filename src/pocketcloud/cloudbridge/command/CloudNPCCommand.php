<?php

namespace pocketcloud\cloudbridge\command;

use pocketcloud\cloudbridge\module\npc\form\NPCMainForm;
use pocketcloud\cloudbridge\utils\Message;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class CloudNPCCommand extends Command {

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->setPermission("pocketcloud.command.cloudnpc");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($sender->hasPermission($this->getPermission())) {
                $sender->sendForm(new NPCMainForm());
            } else {
                Message::parse(Message::NO_PERMISSIONS)->target($sender);
            }
        }
        return true;
    }
}