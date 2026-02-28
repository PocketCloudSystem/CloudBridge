<?php

namespace pocketcloud\cloud\bridge\command;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\Server;

final class TransferCommand extends Command implements PluginOwned {

    public function __construct() {
        parent::__construct("transfer", LanguageKey::INGAME_COMMAND_DESCRIPTION_TRANSFER(), "/transfer <server> [player]");
        $this->setPermission("pocketcloud.command.transfer");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($this->testPermissionSilent($sender)) {
            if (count($args) == 0) return false;
            $server = CloudServerProvider::provider()->get(array_shift($args));
            $target = isset($args[0]) ? Server::getInstance()->getPlayerByPrefix(implode(" ", $args)) : $sender;
            if ($server === null) {
                $sender->sendMessage(LanguageKey::INGAME_SERVER_NOT_FOUND());
                return true;
            }

            if ($target === null) $target = CloudPlayerProvider::provider()->get(implode(" ", $args));
            if ($target instanceof Player || $target instanceof CloudPlayer) {
                if ($sender === $target) {
                    $sender->sendMessage(LanguageKey::INGAME_SERVER_CONNECT()->translate([$server->getName()]));
                } else {
                    $sender->sendMessage(LanguageKey::INGAME_SERVER_TARGET_CONNECT()->translate([$target->getName(), $server->getName()]));
                    $target->sendMessage(LanguageKey::INGAME_SERVER_CONNECT()->translate([$server->getName()]));
                }

                if (!CloudPlayerProvider::provider()->transfer($target, $server)) {
                    if ($sender === $target) {
                        $sender->sendMessage(LanguageKey::INGAME_SERVER_CONNECT_FAILED()->translate([$server->getName()]));
                    } else {
                        $sender->sendMessage(LanguageKey::INGAME_SERVER_TARGET_CONNECT_FAILED()->translate([$target->getName(), $server->getName()]));
                        $target->sendMessage(LanguageKey::INGAME_SERVER_CONNECT_FAILED()->translate([$server->getName()]));
                    }
                }
            } else $sender->sendMessage(LanguageKey::INGAME_PLAYER_NOT_FOUND());
        } else $sender->sendMessage(LanguageKey::INGAME_NO_PERMISSION());
        return true;
    }

    public function getOwningPlugin(): CloudBridge {
        return CloudBridge::getInstance();
    }
}