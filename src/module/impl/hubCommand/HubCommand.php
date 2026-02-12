<?php

namespace pocketcloud\cloud\bridge\module\imp\hubCommand;

use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

final class HubCommand extends Command implements PluginOwned {

    public function __construct() {
        parent::__construct("hub", "Connect to a lobby server", "/hub", ["lobby"]);
        $this->setPermission("pocketcloud.command.hub");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if (!TemplateProvider::provider()->current()->isLobby()) {
                $availableTemplates = TemplateProvider::provider()->pick(fn(Template $template) => $template->isLobby() && !$template->isMaintenance());
                if (!empty($availableTemplates)) {
                    $pickedTemplate = $availableTemplates[array_rand($availableTemplates)];
                    if ($pickedTemplate !== null) {
                        $lobbyServer = CloudServerProvider::provider()->freeServer($pickedTemplate);
                        if ($lobbyServer !== null) {
                            $sender->sendMessage(LanguageKey::INGAME_SERVER_CONNECT()->translate([$lobbyServer->getName()]));
                            if (!CloudPlayerProvider::provider()->transfer($sender, $lobbyServer)) {
                                $sender->sendMessage(LanguageKey::INGAME_SERVER_CONNECT_FAILED()->translate([$lobbyServer->getName()]));
                            }
                        } else {
                            $sender->sendMessage(LanguageKey::INGAME_SERVER_NOT_FOUND());
                        }
                    } else {
                        $sender->sendMessage(LanguageKey::INGAME_SERVER_NOT_FOUND());
                    }
                } else {
                    $sender->sendMessage(LanguageKey::INGAME_SERVER_NOT_FOUND());
                }
            } else {
                $sender->sendMessage(LanguageKey::INGAME_ALREADY_IN_LOBBY());
            }
        }
        return true;
    }
    
    public function getOwningPlugin(): Plugin {
        return CloudBridge::getInstance();
    }
}