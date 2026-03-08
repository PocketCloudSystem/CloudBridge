<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\command;

use pocketcloud\cloud\bridge\command\BaseCloudCommand;
use pocketcloud\cloud\bridge\module\impl\npc\form\TemplateGroupMainForm;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class TemplateGroupCommand extends BaseCloudCommand {

    public function __construct() {
        parent::__construct("templategroup", LanguageKey::INGAME_COMMAND_DESCRIPTION_TEMPLATE_GROUP());
        $this->setPermission("pocketcloud.command.template_group");
    }

    public function run(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($this->testPermissionSilent($sender)) {
                $sender->sendForm(new TemplateGroupMainForm());
            } else $sender->sendMessage(LanguageKey::INGAME_NO_PERMISSION());
        }
        return true;
    }
}