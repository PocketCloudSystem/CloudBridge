<?php

namespace pocketcloud\cloudbridge\command;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\utils\Message;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;

class TransferCommand extends Command {

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->setPermission("pocketcloud.command.transfer");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if (isset($args[0])) {
                $player = $sender;
                if (isset($args[1])) $player = Server::getInstance()->getPlayerExact($args[1]) ?? $sender;

                if (($server = CloudAPI::getInstance()->getServerByName($args[0])) !== null) {
                    if ($sender === $player) {
                        if ($server->getName() == CloudAPI::getInstance()->getServerName()) {
                            Message::parse(Message::ALREADY_CONNECTED, [$server->getName()])->target($sender);
                        } else {
                            Message::parse(Message::CONNECT_TO_SERVER, [$server->getName()])->target($sender);
                            if (!CloudAPI::getInstance()->transferPlayer($sender, $server)) {
                                Message::parse(Message::CANT_CONNECT, [$server->getName()])->target($sender);
                            }
                        }
                    } else {
                        if ($server->getName() == CloudAPI::getInstance()->getServerName()) {
                            Message::parse(Message::ALREADY_CONNECTED_TARGET, [$player->getName(), $server->getName()])->target($sender);
                        } else {
                            Message::parse(Message::CONNECT_TO_SERVER_TARGET, [$player->getName(), $server->getName()])->target($sender);
                            Message::parse(Message::CONNECT_TO_SERVER, [$server->getName()])->target($player);
                            if (!CloudAPI::getInstance()->transferPlayer($player, $server)) {
                                Message::parse(Message::CANT_CONNECT, [$server->getName()])->target($player);
                                Message::parse(Message::CANT_CONNECT_TARGET, [$player->getName(), $server->getName()])->target($sender);
                            }
                        }
                    }
                } else {
                    Message::parse(Message::SERVER_EXISTENCE)->target($sender);
                }
            } else {
                Message::parse(Message::TRANSFER_HELP_USAGE)->target($sender);
            }
        }
        return true;
    }
}