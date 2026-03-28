<?php

namespace pocketcloud\cloud\bridge\api\provider;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\network\packet\impl\PlayerTransferPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Internet;

final class CloudPlayerProvider implements CloudAPIProvider {
    use CloudAPIGetProviderTrait;

    /** @var array<CloudPlayer> */
    private array $players = [];

    public function transfer(Player|CloudPlayer $player, CloudServer $server, bool $useCustomMaxPlayerCount = false): bool {
        $cloudPlayer = $player instanceof Player ? $this->get($player) : $player;
        $serverPlayer = $player instanceof Player ? $player : ($cloudPlayer !==
        null ? Server::getInstance()->getPlayerExact($cloudPlayer->getName()) : null);
        if ($cloudPlayer !== null) {
            if (($useCustomMaxPlayerCount ? count($server->getPlayers()) >=
                    $server->getServerData()->getMaxPlayers() : !$server->getServerStatus()->isOnline()) ||
                $server->getServerStatus()->isStopping()) return false;
            if ($server->getTemplate()->isMaintenance()) {
                if ($serverPlayer !== null &&
                    !$serverPlayer->hasPermission("pocketcloud.bypass.maintenance")) return false;
                else if ($serverPlayer === null) return false;
            }

            if ($cloudPlayer->getCurrentProxy() === null && $serverPlayer !== null) {
                return $serverPlayer->transfer(Internet::getInternalIP(), $server->getServerData()->getPort());
            }

            if ($serverPlayer === null) {
                return PlayerTransferPacket::create($cloudPlayer->getName(), $server->getName())->sendPacket();
            }

            return $serverPlayer->getNetworkSession()->sendDataPacket(TransferPacket::create($server->getName(), $server->getServerData()->getPort(), false));
        }
        return false;
    }

    public function get(Player|string $name): ?CloudPlayer {
        $name = $name instanceof Player ? $name->getName() : $name;
        return $this->players[$name]
            ??
            array_find($this->players, fn(CloudPlayer $player) => $player->getXboxUserId() == $name ||
                $player->getUniqueId() == $name);
    }

    public function add(CloudPlayer $player): void {
        if ($this->isset($player)) $this->players[$player->getName()]->sync($player->write());
        else $this->players[$player->getName()] = $player;
    }

    public function isset(CloudPlayer|string $name): bool {
        $name = $name instanceof CloudPlayer ? $name->getName() : $name;
        return isset($this->players[$name]);
    }

    public function remove(CloudPlayer $player): void {
        if ($this->isset($player)) unset($this->players[$player->getName()]);
    }

    public function getAll(?Template $template = null): array {
        if ($template !== null) {
            return array_filter($this->players, fn(CloudPlayer $player) => $player->getCurrentServerName() !== null && str_starts_with($player->getCurrentServerName(), $template->getName()) || str_starts_with($player->getCurrentProxyName(), $template->getName()));
        }

        return $this->players;
    }
}