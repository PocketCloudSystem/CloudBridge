<?php

namespace pocketcloud\cloudbridge\listener;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\player\CloudPlayer;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerConnectPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerDisconnectPacket;
use pocketcloud\cloudbridge\network\packet\impl\request\CheckPlayerMaintenanceRequestPacket;
use pocketcloud\cloudbridge\network\packet\impl\response\CheckPlayerMaintenanceResponsePacket;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;
use pocketcloud\cloudbridge\network\request\RequestManager;
use pocketcloud\cloudbridge\utils\Message;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\Listener;

class EventListener implements Listener {

    public function onLogin(PlayerLoginEvent $event) {
        $player = $event->getPlayer();
        Network::getInstance()->sendPacket(new PlayerConnectPacket(new CloudPlayer($player->getName(), $player->getNetworkSession()->getIp() . ":" . $player->getNetworkSession()->getPort(), $player->getXuid(), $player->getUniqueId()->toString())));

        if (CloudAPI::getInstance()->getCurrentTemplate()?->isMaintenance()) {
            RequestManager::getInstance()->sendRequest(new CheckPlayerMaintenanceRequestPacket($player->getName()))->then(function(ResponsePacket $packet) use($player): void {
                if ($packet instanceof CheckPlayerMaintenanceResponsePacket) {
                    if (!$packet->getValue() && !$player->hasPermission("pocketcloud.maintenance.bypass")) {
                        $player->kick(Message::parse(Message::TEMPLATE_MAINTENANCE));
                    }
                }
            });
        }
    }

    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        Network::getInstance()->sendPacket(new PlayerDisconnectPacket(new CloudPlayer($player->getName(), $player->getNetworkSession()->getIp() . ":" . $player->getNetworkSession()->getPort(), $player->getXuid(), $player->getUniqueId()->toString())));
    }
}