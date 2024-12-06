<?php

namespace pocketcloud\cloud\bridge\command;

use pocketcloud\cloud\bridge\api\CloudAPI;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\util\GeneralSettings;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class TransferCommand extends Command {

    public function __construct() {
        parent::__construct("transfer", Language::current()->translate("inGame.command.description.transfer"), "/transfer");
        $this->setPermission("pocketcloud.command.transfer");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if (isset($args[0])) {
                $player = $sender;
                if (isset($args[1])) $player = CloudAPI::players()->get($args[1]);

                if ($player === null) {
                    $sender->sendMessage(Language::current()->translate("inGame.player.not.found"));
                    return true;
                }

                if (($server = CloudAPI::servers()->get($args[0])) !== null) {
                    if ($sender->getName() === $player->getName()) {
                        if ($server->getName() == GeneralSettings::getServerName()) {
                            $sender->sendMessage(Language::current()->translate("inGame.server.already.connected", $server->getName()));
                        } else {
                            $sender->sendMessage(Language::current()->translate("inGame.server.connect", $server->getName()));
                            if (!CloudAPI::players()->transfer($sender, $server)) {
                                $sender->sendMessage(Language::current()->translate("inGame.server.connect.failed", $server->getName()));
                            }
                        }
                    } else {
                        if ($server->getName() == $player->getCurrentServer()?->getName()) {
                            $sender->sendMessage(Language::current()->translate("inGame.server.target.already.connected", $player->getName(), $player->getCurrentServer()?->getName()));
                        } else {
                            $sender->sendMessage(Language::current()->translate("inGame.server.target.connect", $player->getName(), $server->getName()));
                            $player->sendMessage(Language::current()->translate("inGame.server.connect", $server->getName()));
                            if (!CloudAPI::players()->transfer($player, $server)) {
                                $sender->sendMessage(Language::current()->translate("inGame.server.target.connect.failed", $player->getName(), $server->getName()));
                                $player->sendMessage(Language::current()->translate("inGame.server.connect.failed", $server->getName()));
                            }
                        }
                    }
                } else $sender->sendMessage(Language::current()->translate("inGame.server.not.found"));
            } else $sender->sendMessage(CloudBridge::getPrefix() . "§c/transfer <server> [target]");
        }
        return true;
    }
}