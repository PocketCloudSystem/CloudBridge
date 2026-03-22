<?php

namespace pocketcloud\cloud\bridge\command\util;

use pocketcloud\cloud\bridge\api\cache\InGameModuleCache;
use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\ServerGroupProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\Module;
use pocketcloud\cloud\bridge\module\ModuleManager;
use pocketcloud\cloud\bridge\network\packet\data\ServerErrorReason;
use pocketcloud\cloud\bridge\network\packet\data\TextType;
use pocketcloud\cloud\bridge\network\packet\impl\request\ServerSaveRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\ServerSaveResponsePacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\ServerStartResponsePacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\ServerStopResponsePacket;
use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\network\packet\RequestPacketFailureReason;
use pocketmine\command\CommandSender;
use Throwable;

final class SubCommandExecutors {

    private static function handleServerErrorReason(CommandSender $sender, ServerErrorReason $errorReason, RequestPacket $requestPacket, string $subject = "NULL"): void {
        if ($errorReason === ServerErrorReason::TEMPLATE_EXISTENCE) {
            $sender->sendMessage(LanguageKey::INGAME_TEMPLATE_NOT_FOUND());
        } else if ($errorReason === ServerErrorReason::MAX_SERVERS) {
            $sender->sendMessage(LanguageKey::INGAME_MAX_SERVERS_REACHED()->translate([$subject]));
        } else if ($errorReason === ServerErrorReason::REQUEST_TIMEOUT) {
            $sender->sendMessage("§cCloud request timeout");
        } else if ($errorReason === ServerErrorReason::SERVER_EXISTENCE) {
            $sender->sendMessage(LanguageKey::INGAME_SERVER_NOT_FOUND());
        } else if ($errorReason === ServerErrorReason::NONE) {
            if ($requestPacket instanceof ServerSaveRequestPacket) {
                $sender->sendMessage(LanguageKey::INGAME_SERVER_SAVED());
            }
        }
    }

    private static function handleRequestTimeout(RequestPacket $requestPacket, CommandSender $sender, ?Throwable $e, ?RequestPacketFailureReason $failureReason): void {
        if ($e !== null) {
            CloudBridge::getInstance()->getLogger()->logException($e);
            $sender->sendMessage("§cSomething unexpected happened: An error occurred. (" . ($failureReason?->name ?? "Unknown failure reason") . ")");
            $sender->sendMessage($e->getMessage());
            return;
        }

        $sender->sendMessage("§8[§b" . $requestPacket->getName() . "§8/§c" . $requestPacket->getRequestId() . "§8] §cRequest timed out");
    }

    public static function handleStartSub(CommandSender $sender, string $commandLabel, array $args): bool {
        /** @var Template $template */
        $template = $args["template"];
        $count = $args["count"] ?? 1;
        if ($count < 1) $count = 1;

        ($pk = CloudServerProvider::provider()->start($template, $count))
            ->then(fn(ServerStartResponsePacket $packet) => self::handleServerErrorReason($sender, $packet->getErrorReason(), $pk, $template->getName()))
            ->failure(fn(RequestPacket $packet, ?Throwable $e, ?RequestPacketFailureReason $failureReason) => self::handleRequestTimeout($pk, $sender, $e, $failureReason));

        return true;
    }

    public static function handleStopSub(CommandSender $sender, string $commandLabel, array $args): bool {
        $object = $args["object"];
        $forcefully = $args["forcefully"] ?? false;

        ($pk = CloudServerProvider::provider()->stop($object, $forcefully))
            ->then(fn(ServerStopResponsePacket $packet) => self::handleServerErrorReason($sender, $packet->getErrorReason(), $pk, $object))
            ->failure(fn(RequestPacket $packet, ?Throwable $e, ?RequestPacketFailureReason $failureReason) => self::handleRequestTimeout($pk, $sender, $e, $failureReason));

        return true;
    }

    public static function handleSaveSub(CommandSender $sender, string $commandLabel, array $args): bool {
        /** @var CloudServer $server */
        $server = $args["server"];

        ($pk = CloudServerProvider::provider()->save($server))
            ->then(fn(ServerSaveResponsePacket $packet) => self::handleServerErrorReason($sender, $packet->getErrorReason(), $pk))
            ->failure(fn(RequestPacket $packet, ?Throwable $e, ?RequestPacketFailureReason $failureReason) => self::handleRequestTimeout($pk, $sender, $e, $failureReason));

        return true;
    }

    public static function handleEnableModuleSub(CommandSender $sender, string $commandLabel, array $args): bool {
        /** @var Module $module */
        $module = $args["module"];

        if ($module->isEnabled()) {
            $sender->sendMessage(LanguageKey::INGAME_MODULE_ALREADY_ENABLED()->translate([$module->getName()]));
            return true;
        }

        ModuleManager::getInstance()->enable($module);
        $sender->sendMessage(LanguageKey::INGAME_MODULE_ENABLED()->translate([$module->getName()]));
        return true;
    }

    public static function handleDisableModuleSub(CommandSender $sender, string $commandLabel, array $args): bool {
        /** @var Module $module */
        $module = $args["module"];

        if ($module->isEnabled()) {
            $sender->sendMessage(LanguageKey::INGAME_MODULE_ALREADY_DISABLED()->translate([$module->getName()]));
            return true;
        }

        ModuleManager::getInstance()->enable($module);
        $sender->sendMessage(LanguageKey::INGAME_MODULE_DISABLED()->translate([$module->getName()]));
        return true;
    }

    public static function handleTextSub(array $args): bool {
        /** @var CloudPlayer $player */
        $player = $args["player"];
        /** @var TextType $textType */
        $textType = $args["type"];
        $message = $args["message"];

        if ($player->send($message, $textType)) {
            $player->sendMessage(Language::current()->translate("inGame.text.successful." . strtolower($textType->getName()), [$player->getName()]));
        }

        return true;
    }

    public static function handleKickSub(array $args): bool {
        /** @var CloudPlayer $player */
        $player = $args["player"];
        $reason = $args["reason"] ?? "";
        $disconnectScreenMessage = $args["disconnectScreenMessage"] ?? "";

        if ($player->kick($reason, $disconnectScreenMessage)) {
            $player->sendMessage(Language::current()->translate("inGame.kick.successful", [$player->getName()]));
        }

        return true;
    }

    public static function handleListSub(CommandSender $sender, string $commandLabel, array $args): bool {
        $type = $args["type"] ?? "servers";

        switch ($type) {
            case "servers": {
                $sender->sendMessage(LanguageKey::INGAME_PREFIX() . "§7Servers §8(§b" . count($servers = CloudServerProvider::provider()->getAll()) . "§8)§7:");
                foreach ($servers as $server) {
                    $sender->sendMessage(
                        LanguageKey::INGAME_PREFIX() ."§b" . $server->getName() .
                        " §8- §7Port: §b" . $server->getServerData()->getPort() . " §8| §7IPv6: §b" . $server->getServerData()->getPort()+1 .
                        " §8- §7Template: §b" . $server->getTemplate()->getName() .
                        " §8- §7Players: §b" . $server->getPlayerCount() . "§8/§b" . $server->getServerData()->getMaxPlayers() . " §8(§b" . $server->getTemplate()->getMaxPlayerCount() . "§8)" .
                        " §8- §7Status: §b" . $server->getServerStatus()->getDisplay()
                    );
                }
                break;
            }
            case "templates": {
                $sender->sendMessage(LanguageKey::INGAME_PREFIX() . "§7Templates §8(§b" . count($templates = TemplateProvider::provider()->getAll()) . "§8)§7:");
                foreach ($templates as $template) {
                    $sender->sendMessage(
                        LanguageKey::INGAME_PREFIX() . "§b" . $template->getName() .
                        " §8- §7isLobby: §a" . ($template->isLobby() ? "§aYES" : "§cNO") .
                        " §8- §7isMaintenance: §a" . ($template->isMaintenance() ? "§aYES" : "§cNO") .
                        " §8- §7MinServerCount: §b" . $template->getMinServerCount() .
                        " §8- §7MaxServerCount: §b" . $template->getMaxServerCount() .
                        " §8- §7isAutoStart: §a" . ($template->isAutoStart() ? "§aYES" : "§cNO") .
                        " §8- §7Type: §b" . strtoupper($template->getTemplateType()) .
                        " §8- §7Players: §b" . $template->getPlayerCount()
                    );
                }
                break;
            }
            case "modules": {
                $sender->sendMessage(LanguageKey::INGAME_PREFIX() . "§7Modules §8(§b" . count($modules = InGameModuleCache::getModuleStates()) . "§8)§7:");
                foreach ($modules as $module => $enabled) {
                    $sender->sendMessage(LanguageKey::INGAME_PREFIX() . "§b" . $module . " §8-> §a" . ($enabled ? "Enabled" : "§cDisabled"));
                }
                break;
            }
            case "players": {
                $sender->sendMessage(LanguageKey::INGAME_PREFIX() . "§7Players §8(§b" . count($players = CloudPlayerProvider::provider()->getAll()) . "§8)§7:");
                foreach ($players as $player) {
                    $sender->sendMessage(
                        LanguageKey::INGAME_PREFIX() . "§b" . $player->getName() .
                        " §8- §7Server: §b" . ($player->getCurrentServerName() ?? "§cNone") .
                        " §8- §7Proxy: §b" . ($player->getCurrentProxyName() ?? "§cNone")
                    );
                }
                break;
            }
            case "groups": {
                $sender->sendMessage(LanguageKey::INGAME_PREFIX() . "§7ServerGroups §8(§b" . count($groups = ServerGroupProvider::provider()->getAll()) . "§8)§7:");
                foreach ($groups as $group) {
                    $sender->sendMessage(
                        LanguageKey::INGAME_PREFIX() . "§b" . $group->getName() .
                        " §8- §7Templates: §8[§b" . implode("§8, §b", $group->getTemplates()) . "§8]" .
                        " §8- §7Players: §b" . $group->getPlayerCount()
                    );
                }
                break;
            }
        }

        return true;
    }
}