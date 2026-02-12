<?php

namespace pocketcloud\cloud\bridge\api\provider;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\network\packet\impl\ProxyPlayerTransferPacket;
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
        $serverPlayer = $player instanceof Player ? $player : ($cloudPlayer !== null ? Server::getInstance()->getPlayerExact($cloudPlayer->getName()) : null);
        if ($cloudPlayer !== null) {
            if (($useCustomMaxPlayerCount ? count($server->getPlayers()) >= $server->getServerData()->getMaxPlayers() : !$server->getServerStatus()->isOnline()) || $server->getServerStatus()->isStopping()) return false;
            if ($server->getTemplate()->isMaintenance() && !$player?->hasPermission("pocketcloud.maintenance.bypass")) return false;

            if ($player->getCurrentProxy() === null && $serverPlayer !== null) {
                return $serverPlayer->transfer(Internet::getInternalIP(), $server->getServerData()->getPort());
            }

            if ($serverPlayer === null) {
                return ProxyPlayerTransferPacket::create($cloudPlayer->getName(), $server->getName())->sendPacket();
            }

            return $serverPlayer->getNetworkSession()->sendDataPacket(TransferPacket::create($server->getName(), $server->getServerData()->getPort(), false));
        }
        return false;
    }

    public function add(CloudPlayer $player): void {
        if ($this->isset($player)) $this->players[$player->getName()]->sync($player->write());
        else $this->players[$player->getName()] = $player;
    }

    public function remove(CloudPlayer $player): void {
        if ($this->isset($player)) unset($this->players[$player->getName()]);
    }

    public function isset(CloudPlayer|string $name): bool {
        $name = $name instanceof CloudPlayer ? $name->getName() : $name;
        return isset($this->players[$name]);
    }

    public function get(Player|string $name): ?CloudPlayer {
        $name = $name instanceof Player ? $name->getName() : $name;
        return $this->players[$name] ?? array_find($this->players, fn(CloudPlayer $player) => $player->getXboxUserId() == $name || $player->getUniqueId() == $name);
    }

    public function getAll(): array {
        return $this->players;
    }
}