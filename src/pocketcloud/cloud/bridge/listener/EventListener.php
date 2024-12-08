<?php

namespace pocketcloud\cloud\bridge\listener;

use pocketcloud\cloud\bridge\api\CloudAPI;
use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\impl\normal\PlayerConnectPacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\PlayerDisconnectPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\CheckPlayerMaintenanceRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\CheckPlayerNotifyRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\CheckPlayerMaintenanceResponsePacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\CheckPlayerNotifyResponsePacket;
use pocketcloud\cloud\bridge\network\request\RequestManager;
use pocketcloud\cloud\bridge\util\NotifyList;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;

final class EventListener implements Listener {

    public function onLogin(PlayerLoginEvent $event): void {
        $player = $event->getPlayer();
        Network::getInstance()->sendPacket(new PlayerConnectPacket(CloudPlayer::fromPlayer($player)));

        if (CloudAPI::templates()->current()->isMaintenance()) {
            CheckPlayerMaintenanceRequestPacket::makeRequest($player->getName())
                ->then(function (CheckPlayerMaintenanceResponsePacket $packet) use($player): void {
                    if (!$packet->getValue() && !$player->hasPermission("pocketcloud.maintenance.bypass")) {
                        $player->kick("§cThis server is in maintenance.");
                    }
                });
        }

        CheckPlayerNotifyRequestPacket::makeRequest($player->getName())
            ->then(function (CheckPlayerNotifyResponsePacket $packet) use($player): void {
                if ($packet->getValue()) NotifyList::put($player);
            });
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        Network::getInstance()->sendPacket(new PlayerDisconnectPacket($player->getName()));
    }
}