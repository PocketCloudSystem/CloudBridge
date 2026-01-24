<?php

namespace pocketcloud\cloud\bridge\command;

use pocketcloud\cloud\bridge\api\cache\NotificationListCache;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

final class CloudNotifyCommand extends Command implements PluginOwned {

    public function __construct() {
        parent::__construct("cloudnotify", LanguageKey::INGAME_COMMAND_DESCRIPTION_CLOUD_NOTIFY(), "/cloudnotify");
        $this->setPermission("pocketcloud.command.notify");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($this->testPermissionSilent($sender)) {
                if (NotificationListCache::is($sender->getName())) {
                    $sender->sendMessage(LanguageKey::INGAME_NOTIFY_DEACTIVATED());
                    NotificationListCache::remove($sender->getName());
                } else {
                    $sender->sendMessage(LanguageKey::INGAME_NOTIFY_ACTIVATED());
                    NotificationListCache::add($sender->getName());
                }
            } else $sender->sendMessage(LanguageKey::INGAME_NO_PERMISSION());
        }
        return true;
    }

    public function getOwningPlugin(): Plugin {
        return CloudBridge::getInstance();
    }
}