<?php

namespace pocketcloud\cloud\bridge\command;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\form\CloudMainForm;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

final class CloudCommand extends Command implements PluginOwned {

    public function __construct() {
        parent::__construct("cloud", LanguageKey::INGAME_COMMAND_DESCRIPTION_CLOUD(), "/cloud");
        $this->setPermission("pocketcloud.command.cloud");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($this->testPermissionSilent($sender)) {
                if (count($args) == 0) {
                    $sender->sendForm(new CloudMainForm());
                    return true;
                }
                //todo: form & subcommand handling
            } else $sender->sendMessage(LanguageKey::INGAME_NO_PERMISSION());
        }
        return true;
    }

    public function getOwningPlugin(): Plugin {
        return CloudBridge::getInstance();
    }
}