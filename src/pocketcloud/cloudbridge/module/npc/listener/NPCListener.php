<?php

namespace pocketcloud\cloudbridge\module\npc\listener;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\api\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\npc\CloudNPCManager;
use pocketcloud\cloudbridge\util\GeneralSettings;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;

class NPCListener implements Listener {

    public function onMove(PlayerMoveEvent $event) {
        if (CloudNPCManager::isEnabled()) {
            $player = $event->getPlayer();

            foreach (CloudNPCManager::getInstance()->getCloudNPCs() as $cloudNPC) {
                if (($entity = CloudNPCManager::getInstance()->getCloudNPCEntity($cloudNPC)) !== null) {
                    if ($entity->getPosition()->distance($player->getPosition()) <= 9) {
                        $horizontal = sqrt(($player->getPosition()->x - $entity->getPosition()->x) ** 2 + ($player->getPosition()->z - $entity->getLocation()->z) ** 2);
                        $vertical = $player->getPosition()->y - $entity->getLocation()->getY();
                        $pitch = -atan2($vertical, $horizontal) / M_PI * 180;

                        $xDist = $player->getPosition()->x - $entity->getLocation()->x;
                        $zDist = $player->getPosition()->z - $entity->getLocation()->z;

                        $yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
                        if ($yaw < 0) $yaw += 360.0;

                        $player->getNetworkSession()->sendDataPacket(MoveActorAbsolutePacket::create($entity->getId(), Position::fromObject($entity->getOffsetPosition($entity->getPosition()), $entity->getWorld()), $pitch, $yaw, $yaw, 0));
                    }
                }
            }
        }
    }

    public function onHit(EntityDamageByEntityEvent $event) {
        if (CloudNPCManager::isEnabled()) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();

            if (($cloudNPC = CloudNPCManager::getInstance()->getCloudNPC($entity->getPosition())) !== null) {
                $event->cancel();
                if ($damager instanceof Player) {
                    if (isset(CloudBridge::getInstance()->npcDetection[$damager->getName()])) {
                        unset(CloudBridge::getInstance()->npcDetection[$damager->getName()]);
                        CloudNPCManager::getInstance()->removeCloudNPC($cloudNPC);
                        $damager->sendMessage(Language::current()->translate("inGame.cloudnpc.removed"));
                    } else {
                        $servers = array_filter(CloudAPI::getInstance()->getServersByTemplate($cloudNPC->getTemplate()), fn(CloudServer $server) => $server->getName() !== GeneralSettings::getServerName() && $server->getServerStatus() === ServerStatus::ONLINE() && !($server->getTemplate()->isMaintenance() && !$damager->hasPermission("pocketcloud.maintenance.bypass")));
                        $damager->sendForm(new MenuForm(
                            Language::current()->translate("inGame.ui.cloudnpc.choose_server.title", $cloudNPC->getTemplate()->getName()),
                            Language::current()->translate("inGame.ui.cloudnpc.choose_server.text", count($servers), $cloudNPC->getTemplate()->getName()),
                            (count($servers) == 0 ? [new MenuOption(Language::current()->translate("inGame.ui.cloudnpc.choose_server.no.server"))] : array_map(fn(CloudServer $server) => new MenuOption(Language::current()->translate("inGame.ui.cloudnpc.choose_server.button.server", $server->getName(), count($server->getCloudPlayers()), $server->getCloudServerData()->getMaxPlayers())), $servers)),
                            function(Player $player, int $data) use($servers): void {
                                /** @var CloudServer $server */
                                if (($server = ($servers[(array_keys($servers)[$data] ?? 0)] ?? null)) instanceof CloudServer) {
                                    $player->sendMessage(Language::current()->translate("inGame.server.connect", $server->getName()));
                                    if (!CloudAPI::getInstance()->transferPlayer($player, $server)) {
                                        $player->sendMessage(Language::current()->translate("inGame.server.connect.failed", $server->getName()));
                                    }
                                }
                            }
                        ));
                    }
                }
            }
        }
    }
    
    public function onJoin(PlayerJoinEvent $e) {
        CloudNPCManager::getInstance()->spawnCloudNPCs();
    }

    public function onInteractWithEntity(DataPacketReceiveEvent $event) {
        if (CloudNPCManager::isEnabled()) {
            $packet = $event->getPacket();
            $origin = $event->getOrigin();
            $player = $origin->getPlayer();

            if ($player instanceof Player) {
                if ($packet instanceof InventoryTransactionPacket) {
                    /** @var UseItemOnEntityTransactionData $trData */
                    if (($trData = $packet->trData) instanceof UseItemOnEntityTransactionData) {
                        if ($trData->getActionType() !== $trData::ACTION_ATTACK) {
                            if (($entity = $player->getWorld()->getEntity($trData->getActorRuntimeId())) !== null) {
                                if (($cloudNPC = CloudNPCManager::getInstance()->getCloudNPC($entity->getPosition())) !== null) {
                                    if (!isset(CloudBridge::getInstance()->npcDelay[$player->getName()])) CloudBridge::getInstance()->npcDelay[$player->getName()] = 0;
                                    if (Server::getInstance()->getTick() >= CloudBridge::getInstance()->npcDelay[$player->getName()]) {
                                        CloudBridge::getInstance()->npcDelay[$player->getName()] = Server::getInstance()->getTick() + 10;
                                        if (($bestServer = CloudAPI::getInstance()->getFreeServerByTemplate($cloudNPC->getTemplate(), [GeneralSettings::getServerName()])) !== null) {
                                            $player->sendMessage(Language::current()->translate("inGame.server.connect", $player->getName()));
                                            if (!CloudAPI::getInstance()->transferPlayer($player, $bestServer)) {
                                                $player->sendMessage(Language::current()->translate("inGame.server.connect.failed", $bestServer->getName()));
                                            }
                                        } else $player->sendMessage(Language::current()->translate("inGame.cloudnpc.quickjoin.no_server"));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
