<?php

namespace pocketcloud\cloudbridge\command;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\form\CloudMainForm;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\globalchat\GlobalChatModule;
use pocketcloud\cloudbridge\module\hubcommand\HubCommandModule;
use pocketcloud\cloudbridge\module\npc\CloudNPCModule;
use pocketcloud\cloudbridge\module\sign\CloudSignModule;
use pocketcloud\cloudbridge\network\packet\impl\response\CloudServerStartResponsePacket;
use pocketcloud\cloudbridge\network\packet\impl\response\CloudServerStopResponsePacket;
use pocketcloud\cloudbridge\network\packet\impl\types\ErrorReason;
use pocketcloud\cloudbridge\network\packet\impl\types\LogType;
use pocketcloud\cloudbridge\network\packet\impl\types\TextType;
use pocketcloud\cloudbridge\util\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use ReflectionClass;

class CloudCommand extends Command {

    public function __construct() {
        parent::__construct("cloud", Language::current()->translate("inGame.command.description.cloud"), "/cloud");
        $this->setPermission("pocketcloud.command.cloud");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($this->testPermissionSilent($sender)) {
                if (isset($args[0])) {
                    $subCommand = strtolower(array_shift($args));
                    if ($subCommand == "start") {
                        if (!isset($args[0])) {
                            $sender->sendForm(new CloudMainForm());
                            return true;
                        }

                        $count = 1;
                        if (isset($args[1])) if (is_numeric($args[1])) if (intval($args[1]) > 0) $count = intval($args[1]);

                        ($pk = CloudAPI::serverProvider()->startServer($args[0], $count))->then(function(CloudServerStartResponsePacket $packet) use($sender, $args): void {
                            if ($packet->getErrorReason() === ErrorReason::TEMPLATE_EXISTENCE()) {
                                $sender->sendMessage(Language::current()->translate("inGame.template.not.found"));
                            } else if ($packet->getErrorReason() === ErrorReason::MAX_SERVERS()) {
                                $sender->sendMessage(Language::current()->translate("inGame.max.servers.reached", $args[0]));
                            }
                        })->failure(function() use($sender, $pk): void {
                            $sender->sendActionBarMessage("§8[§e" . (new ReflectionClass($pk))->getShortName() . "§8/§c" . $pk->getRequestId() . "§8] §cRequest timed out");
                        });
                    } else if ($subCommand == "stop") {
                        if (!isset($args[0])) {
                            $sender->sendForm(new CloudMainForm());
                            return true;
                        }

                        ($pk = CloudAPI::serverProvider()->stopServer($args[0]))->then(function(CloudServerStopResponsePacket $packet) use($sender): void {
                            if ($packet->getErrorReason() === ErrorReason::SERVER_EXISTENCE()) {
                                $sender->sendMessage(Language::current()->translate("inGame.server.not.found"));
                            }
                        })->failure(function() use($sender, $pk): void {
                            $sender->sendActionBarMessage("§8[§e" . (new ReflectionClass($pk))->getShortName() . "§8/§c" . $pk->getRequestId() . "§8] §cRequest timed out");
                        });
                    } else if ($subCommand == "save") {
                        CloudAPI::serverProvider()->saveCurrent();
                        $sender->sendMessage(Language::current()->translate("inGame.server.saved"));
                    } else if ($subCommand == "list") {
                        $type = "servers";
                        if (isset($args[0])) if (strtolower($args[0]) == "templates" || strtolower($args[0]) == "players" || strtolower($args[0]) == "servers" || strtolower($args[0]) == "modules") $type = strtolower($args[0]);

                        if ($type == "templates") {
                            $sender->sendMessage(CloudBridge::getPrefix() . "§7Templates: §8(§e" . count(CloudAPI::getInstance()->templateProvider()->getTemplates()) . "§8)§7:");
                            if (empty(CloudAPI::getInstance()->templateProvider()->getTemplates())) $sender->sendMessage(CloudBridge::getPrefix() . "§7No templates available.");
                            foreach (CloudAPI::getInstance()->templateProvider()->getTemplates() as $template) {
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
                            $sender->sendMessage(CloudBridge::getPrefix() . "§7Servers: §8(§e" . count(CloudAPI::serverProvider()->getServers()) . "§8)§7:");
                            if (empty(CloudAPI::serverProvider()->getServers())) $sender->sendMessage(CloudBridge::getPrefix() . "§7No servers available.");
                            foreach (CloudAPI::serverProvider()->getServers() as $server) {
                                $sender->sendMessage(
                                    CloudBridge::getPrefix() . "§e" . $server->getName() .
                                    " §8- §7Port: §e" . $server->getCloudServerData()->getPort() . " §8| §7IPv6: §e" . $server->getCloudServerData()->getPort()+1 .
                                    " §8- §7Template: §e" . $server->getTemplate()->getName() .
                                    " §8- §7Players: §e" . count($server->getCloudPlayers()) . "§8/§e" . $server->getCloudServerData()->getMaxPlayers() . " §8(§e" . $server->templateProvider()->getTemplate()->getMaxPlayerCount() . "§8)" .
                                    " §8- §7Status: §e" . $server->getServerStatus()->getDisplay()
                                );
                            }
                        } else if ($type == "players") {
                            $sender->sendMessage(CloudBridge::getPrefix() . "§7Players: §8(§e" . count(CloudAPI::getInstance()->getPlayers()) . "§8)§7:");
                            if (empty(CloudAPI::playerProvider()->getPlayers())) $sender->sendMessage(CloudBridge::getPrefix() . "§7No players are online.");
                            foreach (CloudAPI::playerProvider()->getPlayers() as $player) {
                                $sender->sendMessage(
                                    CloudBridge::getPrefix() . "§e" . $player->getName() .
                                    " §8- §7XboxUserId: §e" . $player->getXboxUserId() .
                                    " §8- §7UniqueId: §e" . $player->getUniqueId() .
                                    " §8- §7Server: §e" . ($player->getCurrentServer() === null ? "§cNo server." : $player->getCurrentServer()->getName()) .
                                    " §8- §7Proxy: §e" . ($player->getCurrentProxy() === null ? "§cNo proxy." : $player->getCurrentProxy()->getName())
                                );
                            }
                        } else if ($type == "modules") {
                            $sender->sendMessage(CloudBridge::getPrefix() . "§7Modules: §8(§e4§8)§7:");
                            $sender->sendMessage(CloudBridge::getPrefix() . "§eCloudSignModule §8- §7Status: " . (CloudSignModule::get()->isEnabled() ? "§aEnabled" : "§cDisabled"));
                            $sender->sendMessage(CloudBridge::getPrefix() . "§eCloudNpcModule §8- §7Status: " . (CloudNPCModule::get()->isEnabled() ? "§aEnabled" : "§cDisabled"));
                            $sender->sendMessage(CloudBridge::getPrefix() . "§eHubCommandModule §8- §7Status: " . (HubCommandModule::get()->isEnabled() ? "§aEnabled" : "§cDisabled"));
                            $sender->sendMessage(CloudBridge::getPrefix() . "§eGlobalChatModule §8- §7Status: " . (GlobalChatModule::get()->isEnabled() ? "§aEnabled" : "§cDisabled"));
                        } else $sender->sendForm(new CloudMainForm());
                    } else if ($subCommand == "info") {
                        if (!Utils::containKeys($args, 0, 1)) {
                            $sender->sendForm(new CloudMainForm());
                            return true;
                        }

                        $type = "server";
                        if (isset($args[0])) if (strtolower($args[0]) == "template" || strtolower($args[0]) == "player" || strtolower($args[0]) == "server") $type = strtolower($args[0]);

                        if ($type == "template") {
                            if (($template = CloudAPI::templateProvider()->getTemplate($args[1])) !== null) {
                                $sender->sendMessage(
                                    CloudBridge::getPrefix() . "§e" . $template->getName() .
                                    " §8- §7isLobby: §a" . ($template->isLobby() ? "§aYES" : "§cNO") .
                                    " §8- §7isMaintenance: §a" . ($template->isMaintenance() ? "§aYES" : "§cNO") .
                                    " §8- §7MinServerCount: §e" . $template->getMinServerCount() .
                                    " §8- §7MaxServerCount: §e" . $template->getMaxServerCount() .
                                    " §8- §7isAutoStart: §a" . ($template->isAutoStart() ? "§aYES" : "§cNO") .
                                    " §8- §7Type: §e" . ($template->getTemplateType() === "SERVER" ? "§eSERVER" : "§cPROXY")
                                );
                            } else {
                                $sender->sendMessage(Language::current()->translate("inGame.template.not.found"));
                            }
                        } else if ($type == "server") {
                            if (($server = CloudAPI::serverProvider()->getServer($args[1])) !== null) {
                                $sender->sendMessage(
                                    CloudBridge::getPrefix() . "§e" . $server->getName() .
                                    " §8- §7Port: §e" . $server->getCloudServerData()->getPort() . " §8| §7IPv6: §e" . $server->getCloudServerData()->getPort()+1 .
                                    " §8- §7Template: §e" . $server->getTemplate()->getName() .
                                    " §8- §7Players: §e" . count($server->getCloudPlayers()) . "§8/§e" . $server->getCloudServerData()->getMaxPlayers() .
                                    " §8- §7Status: §e" . $server->getServerStatus()->getDisplay()
                                );
                            } else {
                                $sender->sendMessage(Language::current()->translate("inGame.server.not.found"));
                            }
                        } else if ($type == "player") {
                            if (($player = CloudAPI::playerProvider()->getPlayer($args[1])) !== null) {
                                $sender->sendMessage(
                                    CloudBridge::getPrefix() . "§e" . $player->getName() .
                                    " §8- §7XboxUserId: §e" . $player->getXboxUserId() .
                                    " §8- §7UniqueId: §e" . $player->getUniqueId() .
                                    " §8- §7Server: §e" . ($player->getCurrentServer() === null ? "§cNo server." : $player->getCurrentServer()->getName()) .
                                    " §8- §7Proxy: §e" . ($player->getCurrentProxy() === null ? "§cNo proxy." : $player->getCurrentProxy()->getName())
                                );
                            } else {
                                $sender->sendMessage(Language::current()->translate("inGame.player.not.found"));
                            }
                        } else $sender->sendForm(new CloudMainForm());
                    } else if ($subCommand == "text") {
                        if (!Utils::containKeys($args, 0, 1, 2)) {
                            $sender->sendMessage(CloudBridge::getPrefix() . "§c/cloud text <player> <type> <text>");
                            return true;
                        }

                        $target = array_shift($args);
                        $textType = TextType::getTypeByName(array_shift($args)) ?? TextType::MESSAGE();
                        $text = implode(" ", $args);

                        if (($target = CloudAPI::playerProvider()->getPlayer($target)) !== null) {
                            $sender->sendMessage(Language::current()->translate("inGame.text.successful." . strtolower($textType->getName()), $target->getName()));
                            $target->send($text, $textType);
                        } else {
                            $sender->sendMessage(Language::current()->translate("inGame.player.not.found"));
                        }
                    } else if ($subCommand == "kick") {
                        if (!Utils::containKeys($args, 0)) {
                            $sender->sendMessage(CloudBridge::getPrefix() . "§c/cloud kick <player> [reason]");
                            return true;
                        }

                        $target = array_shift($args);
                        $reason = implode(" ", $args);

                        if (($target = CloudAPI::getInstance()->playerProvider()->getPlayer($target)) !== null) {
                            $sender->sendMessage(Language::current()->translate("inGame.kick.successful", $target->getName()));
                            $target->kick($reason);
                        } else {
                            $sender->sendMessage(Language::current()->translate("inGame.player.not.found"));
                        }
                    } else if ($subCommand == "log") {
                        if (!Utils::containKeys($args, 0, 1)) {
                            $sender->sendMessage(CloudBridge::getPrefix() . "§c/cloud log <logType> <text>");
                            return true;
                        }

                        $logType = LogType::getTypeByName(array_shift($args)) ?? LogType::INFO();
                        $text = implode(" ", $args);

                        CloudAPI::getInstance()->logConsole($text, $logType);
                        $sender->sendMessage(Language::current()->translate("inGame.console.log.successful"));
                    } else if ($subCommand == "enable") {
                        if (!Utils::containKeys($args, 0)) {
                            $sender->sendMessage(CloudBridge::getPrefix() . "§c/cloud enable <module>");
                            return true;
                        }

                        $module = strtolower($args[0]);
                        $moduleNamesSign = ["sign", "sign_module", "signsystem", "signmodule"];
                        $moduleNamesNpc = ["npc", "npc_module", "npcsystem", "npcmodule"];
                        $moduleNamesHub = ["hub", "hubcommand", "hub_module", "hubcommand_module", "hubcommandmodule"];
                        $moduleNamesGG = ["globalchat", "globalchat_module", "globalchatmodule"];
                        if (in_array($module, $moduleNamesSign)) {
                            if (!CloudSignModule::get()->isEnabled()) {
                                CloudSignModule::get()->setEnabled();
                                $sender->sendMessage(Language::current()->translate("inGame.module.enabled", "CloudSignModule"));
                            } else {
                                $sender->sendMessage(Language::current()->translate("inGame.module.already.enabled", "CloudSignModule"));
                            }
                        } else if (in_array($module, $moduleNamesNpc)) {
                            if (!CloudNPCModule::get()->isEnabled()) {
                                CloudNPCModule::get()->setEnabled();
                                $sender->sendMessage(Language::current()->translate("inGame.module.enabled", "CloudNPCModule"));
                            } else {
                                $sender->sendMessage(Language::current()->translate("inGame.module.already.enabled", "CloudNPCModule"));
                            }
                        } else if (in_array($module, $moduleNamesHub)) {
                            if (!HubCommandModule::get()->isEnabled()) {
                                HubCommandModule::get()->setEnabled();
                                $sender->sendMessage(Language::current()->translate("inGame.module.enabled", "HubCommandModule"));
                            } else {
                                $sender->sendMessage(Language::current()->translate("inGame.module.already.enabled", "HubCommandModule"));
                            }
                        } else if (in_array($module, $moduleNamesGG)) {
                            if (!GlobalChatModule::get()->isEnabled()) {
                                GlobalChatModule::get()->setEnabled();
                                $sender->sendMessage(Language::current()->translate("inGame.module.enabled", "GlobalChatModule"));
                            } else {
                                $sender->sendMessage(Language::current()->translate("inGame.module.already.enabled", "GlobalChatModule"));
                            }
                        }
                    } else if ($subCommand == "disable") {
                        if (!Utils::containKeys($args, 0)) {
                            $sender->sendMessage(CloudBridge::getPrefix() . "§c/cloud disable <module>");
                            return true;
                        }

                        $module = strtolower($args[0]);
                        $moduleNamesSign = ["sign", "sign_module", "signsystem", "signmodule"];
                        $moduleNamesNpc = ["npc", "npc_module", "npcsystem", "npcmodule"];
                        $moduleNamesHub = ["hub", "hubcommand", "hub_module", "hubcommand_module", "hubcommandmodule"];
                        $moduleNamesGG = ["globalchat", "globalchat_module", "globalchatmodule"];
                        if (in_array($module, $moduleNamesSign)) {
                            if (CloudSignModule::get()->isEnabled()) {
                                CloudSignModule::get()->setEnabled(false);
                                $sender->sendMessage(Language::current()->translate("inGame.module.disabled", "CloudSignModule"));
                            } else {
                                $sender->sendMessage(Language::current()->translate("inGame.module.already.disabled", "CloudSignModule"));
                            }
                        } else if (in_array($module, $moduleNamesNpc)) {
                            if (CloudNPCModule::get()->isEnabled()) {
                                CloudNPCModule::get()->setEnabled(false);
                                $sender->sendMessage(Language::current()->translate("inGame.module.disabled", "CloudNPCModule"));
                            } else {
                                $sender->sendMessage(Language::current()->translate("inGame.module.already.disabled", "CloudNPCModule"));
                            }
                        } else if (in_array($module, $moduleNamesHub)) {
                            if (HubCommandModule::get()->isEnabled()) {
                                HubCommandModule::get()->setEnabled(false);
                                $sender->sendMessage(Language::current()->translate("inGame.module.disabled", "HubCommandModule"));
                            } else {
                                $sender->sendMessage(Language::current()->translate("inGame.module.already.disabled", "HubCommandModule"));
                            }
                        } else if (in_array($module, $moduleNamesGG)) {
                            if (GlobalChatModule::get()->isEnabled()) {
                                GlobalChatModule::get()->setEnabled(false);
                                $sender->sendMessage(Language::current()->translate("inGame.module.disabled", "GlobalChatModule"));
                            } else {
                                $sender->sendMessage(Language::current()->translate("inGame.module.already.disabled", "GlobalChatModule"));
                            }
                        }
                    } else $sender->sendForm(new CloudMainForm());
                } else $sender->sendForm(new CloudMainForm());
            } else $sender->sendMessage(Language::current()->translate("inGame.no.permission"));
        }
        return true;
    }
}