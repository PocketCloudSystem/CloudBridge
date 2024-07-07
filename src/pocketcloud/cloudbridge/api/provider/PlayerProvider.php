<?php

namespace pocketcloud\cloudbridge\api\provider;

use pocketcloud\cloudbridge\api\object\player\CloudPlayer;
use pocketcloud\cloudbridge\api\object\server\CloudServer;
use pocketcloud\cloudbridge\api\object\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\object\template\Template;
use pocketcloud\cloudbridge\api\registry\Registry;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerTransferPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Internet;

class PlayerProvider {

    public function transferPlayer(Player|CloudPlayer $player, CloudServer $server, bool $useCustomMaxPlayerCount = false): bool {
        $player = ($player instanceof Player ? $this->getPlayer($player->getName()) : $player);
        if ($player !== null) {
            $serverPlayer = $player->getServerPlayer();
            if (($useCustomMaxPlayerCount ? count(Server::getInstance()->getOnlinePlayers()) >= Server::getInstance()->getMaxPlayers() : ($server->getServerStatus() === ServerStatus::IN_GAME() || $server->getServerStatus() === ServerStatus::FULL())) || $server->getServerStatus() === ServerStatus::STOPPING()) return false;
            if ($server->getTemplate()->isMaintenance() && !$serverPlayer?->hasPermission("pocketcloud.maintenance.bypass")) return false;

            if ($player->getCurrentProxy() === null && $serverPlayer !== null) {
                return $serverPlayer->transfer(Internet::getInternalIP(), $server->getCloudServerData()->getPort());
            }

            if ($serverPlayer === null) {
                return Network::getInstance()->sendPacket(new PlayerTransferPacket($player->getName(), $server->getName()));
            }

            return $serverPlayer->getNetworkSession()->sendDataPacket(TransferPacket::create($server->getName(), $server->getCloudServerData()->getPort()));
        }
        return false;
    }

    /** @return array<CloudPlayer> */
    public function getPlayersOfTemplate(Template $template): array {
        return array_filter($this->getPlayers(), function(CloudPlayer $player) use($template): bool {
            if ($template->getTemplateType() == "PROXY") return ($player->getCurrentProxy() !== null && $player->getCurrentProxy()->getTemplate()->getName() == $template->getName());
            else return ($player->getCurrentServer() !== null && $player->getCurrentServer()->getTemplate()->getName() == $template->getName());
        });
    }

    public function getPlayer(string $name): ?CloudPlayer {
        return Registry::getPlayers()[$name] ?? null;
    }

    public function getPlayerByUniqueId(string $uniqueId): ?CloudPlayer {
        return array_values(array_filter(Registry::getPlayers(), fn(CloudPlayer $player) => $player->getUniqueId() == $uniqueId))[0] ?? null;
    }

    public function getPlayerByXboxUserId(string $xboxUserId): ?CloudPlayer {
        return array_values(array_filter(Registry::getPlayers(), fn(CloudPlayer $player) => $player->getXboxUserId() == $xboxUserId))[0] ?? null;
    }

    /** @return array<CloudPlayer> */
    public function getPlayers(): array {
        return Registry::getPlayers();
    }
}