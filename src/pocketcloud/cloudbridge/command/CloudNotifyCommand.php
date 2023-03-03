<?php

namespace pocketcloud\cloudbridge\command;

use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerNotifyUpdatePacket;
use pocketcloud\cloudbridge\network\packet\impl\request\CheckPlayerNotifyRequestPacket;
use pocketcloud\cloudbridge\network\packet\impl\response\CheckPlayerNotifyResponsePacket;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;
use pocketcloud\cloudbridge\network\request\RequestManager;
use pocketcloud\cloudbridge\utils\Message;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class CloudNotifyCommand extends Command {

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->setPermission("pocketcloud.command.notify");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($this->testPermissionSilent($sender)) {
                RequestManager::getInstance()->sendRequest($pk = new CheckPlayerNotifyRequestPacket($sender->getName()))->then(function(ResponsePacket $responsePacket) use($sender): void {
                    if ($responsePacket instanceof CheckPlayerNotifyResponsePacket) {
                        if (!$responsePacket->getValue()) {
                            Network::getInstance()->sendPacket(new PlayerNotifyUpdatePacket($sender->getName(), true));
                            Message::parse(Message::NOTIFICATIONS_ACTIVATED)->target($sender);
                        } else {
                            Network::getInstance()->sendPacket(new PlayerNotifyUpdatePacket($sender->getName(), false));
                            Message::parse(Message::NOTIFICATIONS_DEACTIVATED)->target($sender);
                        }
                    }
                })->failure(function() use($sender, $pk): void {
                    Message::parse(Message::REQUEST_TIMEOUT, [$pk->getRequestId(), $pk->getIdentifier()])->target($sender);
                });
            } else {
                Message::parse(Message::NO_PERMISSIONS)->target($sender);
            }
        }
        return true;
    }
}