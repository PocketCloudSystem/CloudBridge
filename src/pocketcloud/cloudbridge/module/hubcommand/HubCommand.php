<?php

namespace pocketcloud\cloudbridge\module\hubcommand;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\api\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\config\ModulesConfig;
use pocketcloud\cloudbridge\utils\Message;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class HubCommand extends Command {
    use SingletonTrait;

    public function __construct() {
        self::setInstance($this);
        parent::__construct("hub", Message::parse(Message::HUB_COMMAND_DESCRIPTION), "/hub", ["lobby"]);
        if (ModulesConfig::getInstance()->isHubCommandModule()) Server::getInstance()->getCommandMap()->register("hubCommandModule", $this);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if (!CloudAPI::getInstance()->getCurrentTemplate()?->isLobby()) {
                if (($lobbyServer = $this->getLobbyServer($sender)) !== null) {
                    Message::parse(Message::CONNECT_TO_SERVER, [$lobbyServer->getName()])->target($sender);
                    if (!CloudAPI::getInstance()->transferPlayer($sender, $lobbyServer)) {
                        Message::parse(Message::CANT_CONNECT, [$lobbyServer->getName()])->target($sender);
                    }
                } else {
                    Message::parse(Message::NO_SERVER_FOUND)->target($sender);
                }
            } else {
                Message::parse(Message::ALREADY_IN_LOBBY)->target($sender);
            }
        }
        return true;
    }

    private function getLobbyServer(?Player $player = null): ?CloudServer {
        $lobbyServerClasses = [];
        $lobbyServers = [];
        foreach (array_filter(CloudAPI::getInstance()->getServers(), fn(CloudServer $server) => $server->getTemplate()->isLobby()) as $lobbyServer) {
            if ($lobbyServer->getName() === CloudAPI::getInstance()->getServerName() || $lobbyServer->getServerStatus() === ServerStatus::FULL() || $lobbyServer->getServerStatus() === ServerStatus::STOPPING() || $lobbyServer->getServerStatus() === ServerStatus::IN_GAME() || $lobbyServer->getTemplate()->isMaintenance()) continue;
            $lobbyServers[$lobbyServer->getName()] = count($lobbyServer->getCloudPlayers());
            $lobbyServerClasses[$lobbyServer->getName()] = $lobbyServer;
        }

        arsort($lobbyServers);
        return $lobbyServerClasses[array_key_last($lobbyServers)] ?? null;
    }
}