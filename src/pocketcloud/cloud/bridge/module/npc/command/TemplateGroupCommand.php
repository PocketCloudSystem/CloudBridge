<?php

namespace pocketcloud\cloud\bridge\module\npc\command;

use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\module\npc\form\TemplateGroupMainForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class TemplateGroupCommand extends Command {

    public function __construct() {
        parent::__construct("templategroup", Language::current()->translate("inGame.command.description.template_group"), "/templategroup");
        $this->setPermission("pocketcloud.command.template_group");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($this->testPermissionSilent($sender)) {
                $sender->sendForm(new TemplateGroupMainForm());
            } else $sender->sendMessage(Language::current()->translate("inGame.no.permission"));
        }
        return true;
    }
}