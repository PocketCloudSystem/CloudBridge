<?php

namespace pocketcloud\cloud\bridge\module\hubcommand;

use pocketcloud\cloud\bridge\api\CloudAPI;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\language\Language;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

final class HubCommand extends Command {

    public function __construct() {
        parent::__construct("hub", "Connect to a lobby server", "/hub", ["lobby"]);
        $this->setPermission(DefaultPermissions::ROOT_USER);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if (!CloudAPI::templates()->current()->isLobby()) {
                $availableTemplates = CloudAPI::templates()->pick(fn(Template $template) => $template->isLobby() && !$template->isMaintenance());
                if (!empty($availableTemplates)) {
                    $pickedTemplate = $availableTemplates[array_rand($availableTemplates)];
                    if ($pickedTemplate !== null) {
                        $lobbyServer = CloudAPI::servers()->getFreeServer($pickedTemplate);
                        if ($lobbyServer !== null) {
                            $sender->sendMessage(Language::current()->translate("inGame.server.connect", $lobbyServer->getName()));
                            if (!CloudAPI::players()->transfer($sender, $lobbyServer)) {
                                $sender->sendMessage(Language::current()->translate("inGame.server.connect.failed", $lobbyServer->getName()));
                            }
                        } else {
                            $sender->sendMessage(Language::current()->translate("inGame.server.not.found"));
                        }
                    } else {
                        $sender->sendMessage(Language::current()->translate("inGame.server.not.found"));
                    }
                } else {
                    $sender->sendMessage(Language::current()->translate("inGame.server.not.found"));
                }
            } else {
                $sender->sendMessage(Language::current()->translate("inGame.already.in.lobby"));
            }
        }
        return true;
    }
}