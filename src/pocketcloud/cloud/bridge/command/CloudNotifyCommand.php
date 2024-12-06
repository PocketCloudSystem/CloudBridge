<?php

namespace pocketcloud\cloud\bridge\command;

use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\impl\normal\PlayerNotifyUpdatePacket;
use pocketcloud\cloud\bridge\util\NotifyList;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class CloudNotifyCommand extends Command {

    public function __construct() {
        parent::__construct("cloudnotify", Language::current()->translate("inGame.command.description.cloud_notify"), "/cloudnotify");
        $this->setPermission("pocketcloud.command.notify");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($this->testPermissionSilent($sender)) {
                if (NotifyList::exists($sender)) {
                    NotifyList::remove($sender);
                    Network::getInstance()->sendPacket(new PlayerNotifyUpdatePacket($sender->getName(), false));
                    $sender->sendMessage(Language::current()->translate("inGame.notify.deactivated"));
                } else {
                    NotifyList::put($sender);
                    Network::getInstance()->sendPacket(new PlayerNotifyUpdatePacket($sender->getName(), true));
                    $sender->sendMessage(Language::current()->translate("inGame.notify.activated"));
                }
            } else $sender->sendMessage(Language::current()->translate("inGame.no.permission"));
        }
        return true;
    }
}