<?php

namespace pocketcloud\cloud\bridge\command;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\command\util\ParameterType;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class TransferCommand extends BaseCloudCommand {

    public function __construct() {
        parent::__construct("transfer", LanguageKey::INGAME_COMMAND_DESCRIPTION_TRANSFER());
        $this->setPermission("pocketcloud.command.transfer");

        $this->registerParameter(ParameterType::SERVER->with("server", false));
        $this->registerParameter(ParameterType::CLOUD_PLAYER->with("target"));
    }

    public function run(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($this->testPermissionSilent($sender)) {
            /** @var CloudServer $server */
            $server = $args["server"];
            /** @var CloudPlayer|Player $target */
            $target = $args["target"] ?? $sender;

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
}