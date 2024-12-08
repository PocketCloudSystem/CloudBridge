<?php

namespace pocketcloud\cloud\bridge\api\provider;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\server\status\ServerStatus;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\registry\Registry;
use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\impl\normal\PlayerTransferPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\player\Player;
use pocketmine\utils\Internet;

final class PlayerProvider {

    public function transfer(Player|CloudPlayer $player, CloudServer $server, bool $useCustomMaxPlayerCount = false): bool {
        $player = ($player instanceof Player ? $this->get($player->getName()) : $player);
        if ($player !== null) {
            $serverPlayer = $player->getServerPlayer();
            if (($useCustomMaxPlayerCount ? count($server->getCloudPlayers()) >= $server->getCloudServerData()->getMaxPlayers() : ($server->getServerStatus() === ServerStatus::IN_GAME() || $server->getServerStatus() === ServerStatus::FULL())) || $server->getServerStatus() === ServerStatus::STOPPING()) return false;
            if ($server->getTemplate()->isMaintenance() && !$serverPlayer?->hasPermission("pocketcloud.maintenance.bypass")) return false;

            if ($player->getCurrentProxy() === null && $serverPlayer !== null) {
                return $serverPlayer->transfer(Internet::getInternalIP(), $server->getCloudServerData()->getPort());
            }

            if ($serverPlayer === null) {
                return Network::getInstance()->sendPacket(new PlayerTransferPacket($player->getName(), $server->getName()));
            }

            return $serverPlayer->getNetworkSession()->sendDataPacket(TransferPacket::create($server->getName(), $server->getCloudServerData()->getPort(), false));
        }
        return false;
    }

    public function get(string $name): ?CloudPlayer {
        if (isset(Registry::getPlayers()[$name])) return Registry::getPlayers()[$name];
        foreach (Registry::getPlayers() as $player) {
            if ($player->getXboxUserId() == $name || $player->getUniqueId() == $name) return $player;
        }
        return null;
    }

    /** @return array<CloudPlayer> */
    public function getAll(?Template $template = null): array {
        if ($template !== null) return array_filter($this->getAll(), function(CloudPlayer $player) use($template): bool {
            if ($template->getTemplateType() == "PROXY") return ($player->getCurrentProxy() !== null && $player->getCurrentProxy()->getTemplate()->getName() == $template->getName());
            else return ($player->getCurrentServer() !== null && $player->getCurrentServer()->getTemplate()->getName() == $template->getName());
        });
        return Registry::getPlayers();
    }
}