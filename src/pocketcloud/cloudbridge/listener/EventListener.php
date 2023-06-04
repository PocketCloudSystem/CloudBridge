<?php

namespace pocketcloud\cloudbridge\listener;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\player\CloudPlayer;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerConnectPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerDisconnectPacket;
use pocketcloud\cloudbridge\network\packet\impl\request\CheckPlayerMaintenanceRequestPacket;
use pocketcloud\cloudbridge\network\packet\impl\request\CheckPlayerNotifyRequestPacket;
use pocketcloud\cloudbridge\network\packet\impl\response\CheckPlayerMaintenanceResponsePacket;
use pocketcloud\cloudbridge\network\packet\impl\response\CheckPlayerNotifyResponsePacket;
use pocketcloud\cloudbridge\network\request\RequestManager;
use pocketcloud\cloudbridge\util\NotifyList;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener {

    public function onLogin(PlayerLoginEvent $event) {
        $player = $event->getPlayer();
        Network::getInstance()->sendPacket(new PlayerConnectPacket(CloudPlayer::fromPlayer($player)));

        if (CloudAPI::getInstance()->getCurrentTemplate()?->isMaintenance()) {
            RequestManager::getInstance()->sendRequest(new CheckPlayerMaintenanceRequestPacket($player->getName()))->then(function(CheckPlayerMaintenanceResponsePacket $packet) use($player): void {
                if (!$packet->getValue() && !$player->hasPermission("pocketcloud.maintenance.bypass")) {
                    $player->kick(Language::current()->translate("inGame.template.kick.maintenance"));
                }
            });
        }

        RequestManager::getInstance()->sendRequest(new CheckPlayerNotifyRequestPacket($player->getName()))->then(function(CheckPlayerNotifyResponsePacket $packet) use($player): void {
            if ($packet->getValue()) NotifyList::put($player);
        });
    }

    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        Network::getInstance()->sendPacket(new PlayerDisconnectPacket($player->getName()));
    }
}