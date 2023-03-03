<?php

namespace pocketcloud\cloudbridge\command;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\network\packet\impl\response\CloudServerStartResponsePacket;
use pocketcloud\cloudbridge\network\packet\impl\types\ErrorReason;
use pocketcloud\cloudbridge\network\packet\impl\types\TextType;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;
use pocketcloud\cloudbridge\network\packet\impl\response\CloudServerStopResponsePacket;
use pocketcloud\cloudbridge\utils\Message;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class CloudCommand extends Command {

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->setPermission("pocketcloud.command.cloud");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($this->testPermissionSilent($sender)) {
                if (isset($args[0])) {
                    if (strtolower($args[0]) == "start") {
                        if (isset($args[1])) {
                            $count = 1;
                            if (isset($args[2])) if (is_numeric($args[2])) if (intval($args[2]) > 0) $count = intval($args[2]);

                            ($pk = CloudAPI::getInstance()->startServer($args[1], $count))->then(function(ResponsePacket $packet) use($sender): void {
                                if ($packet instanceof CloudServerStartResponsePacket) {
                                    if ($packet->getErrorReason() === ErrorReason::TEMPLATE_EXISTENCE()) {
                                        Message::parse(Message::TEMPLATE_EXISTENCE)->target($sender);
                                    } else if ($packet->getErrorReason() === ErrorReason::MAX_SERVERS()) {
                                        Message::parse(Message::MAX_SERVERS)->target($sender);
                                    }
                                }
                            })->failure(function() use($sender, $pk): void {
                                Message::parse(Message::REQUEST_TIMEOUT, [$pk->getRequestId(), $pk->getIdentifier()])->target($sender, TextType::POPUP());
                            });;
                        } else {
                            Message::parse(Message::CLOUD_START_HELP_USAGE)->target($sender);
                        }
                    } else if (strtolower($args[0]) == "stop") {
                        if (isset($args[1])) {
                            ($pk = CloudAPI::getInstance()->stopServer($args[1]))->then(function(ResponsePacket $packet) use($sender): void {
                                if ($packet instanceof CloudServerStopResponsePacket) {
                                    if ($packet->getErrorReason() === ErrorReason::SERVER_EXISTENCE()) {
                                        Message::parse(Message::SERVER_EXISTENCE)->target($sender);
                                    }
                                }
                            })->failure(function() use($sender, $pk): void {
                                Message::parse(Message::REQUEST_TIMEOUT, [$pk->getRequestId(), $pk->getIdentifier()])->target($sender, TextType::POPUP());
                            });;
                        } else {
                            Message::parse(Message::CLOUD_STOP_HELP_USAGE)->target($sender);
                        }
                    } else if (strtolower($args[0]) == "save") {
                        CloudAPI::getInstance()->saveCurrentServer();
                        Message::parse(Message::SERVER_SAVED)->target($sender);
                    } else if (strtolower($args[0]) == "list" || strtolower($args[0]) == "ls") {
                        $type = "servers";
                        if (isset($args[1])) if (strtolower($args[1]) == "templates" || strtolower($args[1]) == "players" || strtolower($args[1]) == "servers") $type = strtolower($args[1]);

                        if ($type == "templates") {
                            $sender->sendMessage(CloudBridge::getPrefix() . "§7Templates: §8(§e" . count(CloudAPI::getInstance()->getTemplates()) . "§8)§7:");
                            if (empty(CloudAPI::getInstance()->getTemplates())) $sender->sendMessage(CloudBridge::getPrefix() . "§7No templates available.");
                            foreach (CloudAPI::getInstance()->getTemplates() as $template) {
                                $sender->sendMessage(
                                    CloudBridge::getPrefix() . "§e" . $template->getName() .
                                    " §8- §7isLobby: §a" . ($template->isLobby() ? "§aYES" : "§cNO") .
                                    " §8- §7isMaintenance: §a" . ($template->isMaintenance() ? "§aYES" : "§cNO") .
                                    " §8- §7MinServerCount: §e" . $template->getMinServerCount() .
                                    " §8- §7MaxServerCount: §e" . $template->getMaxServerCount() .
                                    " §8- §7isAutoStart: §a" . ($template->isAutoStart() ? "§aYES" : "§cNO") .
                                    " §8- §7Type: §e" . ($template->getTemplateType() === "SERVER" ? "§eSERVER" : "§cPROXY")
                                );
                            }
                        } else if ($type == "servers") {
                            $sender->sendMessage(CloudBridge::getPrefix() . "§7Servers: §8(§e" . count(CloudAPI::getInstance()->getServers()) . "§8)§7:");
                            if (empty(CloudAPI::getInstance()->getServers())) $sender->sendMessage(CloudBridge::getPrefix() . "§7No servers available.");
                            foreach (CloudAPI::getInstance()->getServers() as $server) {
                                $sender->sendMessage(
                                    CloudBridge::getPrefix() . "§e" . $server->getName() .
                                    " §8- §7Port: §e" . $server->getCloudServerData()->getPort() . " §8| §7IPv6: §e" . $server->getCloudServerData()->getPort()+1 .
                                    " §8- §7Template: §e" . $server->getTemplate()->getName() .
                                    " §8- §7Players: §e" . count($server->getCloudPlayers()) . "§8/§e" . $server->getCloudServerData()->getMaxPlayers() .
                                    " §8- §7Status: §e" . $server->getServerStatus()->getDisplay()
                                );
                            }
                        } else if ($type == "players") {
                            $sender->sendMessage(CloudBridge::getPrefix() . "§7Players: §8(§e" . count(CloudAPI::getInstance()->getPlayers()) . "§8)§7:");
                            if (empty(CloudAPI::getInstance()->getPlayers())) $sender->sendMessage(CloudBridge::getPrefix() . "§7No players are online.");
                            foreach (CloudAPI::getInstance()->getPlayers() as $player) {
                                $sender->sendMessage(
                                    CloudBridge::getPrefix() . "§e" . $player->getName() .
                                    " §8- §7XboxUserId: §e" . $player->getXboxUserId() .
                                    " §8- §7UniqueId: §e" . $player->getUniqueId() .
                                    " §8- §7Server: §e" . ($player->getCurrentServer() === null ? "§cNo server." : $player->getCurrentServer()->getName()) .
                                    " §8- §7Proxy: §e" . ($player->getCurrentProxy() === null ? "§cNo proxy." : $player->getCurrentProxy()->getName())
                                );
                            }
                        } else {
                            Message::parse(Message::CLOUD_LIST_HELP_USAGE)->target($sender);
                        }
                    } else {
                        Message::parse(Message::CLOUD_HELP_USAGE)->target($sender);
                    }
                } else {
                    Message::parse(Message::CLOUD_HELP_USAGE)->target($sender);
                }
            } else {
                Message::parse(Message::NO_PERMISSIONS)->target($sender);
            }
        }
        return true;
    }
}