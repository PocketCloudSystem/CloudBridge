<?php

namespace pocketcloud\cloudbridge\module\npc\listener;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\api\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\lib\dktapps\pmforms\MenuForm;
use pocketcloud\cloudbridge\lib\dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\module\npc\CloudNPCManager;
use pocketcloud\cloudbridge\utils\Message;
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

    public function onHit(EntityDamageByEntityEvent $event) {
        $entity = $event->getEntity();
        $damager = $event->getDamager();

        if (($cloudNPC = CloudNPCManager::getInstance()->getCloudNPC($entity->getPosition())) !== null) {
            $event->cancel();
            if ($damager instanceof Player) {
                if (isset(CloudBridge::getInstance()->npcDetection[$damager->getName()])) {
                    unset(CloudBridge::getInstance()->npcDetection[$damager->getName()]);
                    CloudNPCManager::getInstance()->removeCloudNPC($cloudNPC);
                    Message::parse(Message::NPC_REMOVED)->target($damager);
                } else {
                    $servers = array_filter(CloudAPI::getInstance()->getServersOfTemplate($cloudNPC->getTemplate()), fn(CloudServer $server) => $server->getName() !== CloudAPI::getInstance()->getServerName() && $server->getServerStatus() !== ServerStatus::FULL() && $server->getServerStatus() !== ServerStatus::STOPPING() && $server->getServerStatus() !== ServerStatus::IN_GAME() && !($server->getTemplate()->isMaintenance() && !$damager->hasPermission("pocketcloud.maintenance.bypass")));
                    $damager->sendForm(new MenuForm(
                        Message::parse(Message::UI_NPC_CHOOSE_SERVER_TITLE, [$cloudNPC->getTemplate()->getName()]),
                        Message::parse(Message::UI_NPC_CHOOSE_SERVER_TEXT, [count($servers), $cloudNPC->getTemplate()->getName()]),
                        (count($servers) == 0 ? [new MenuOption(Message::parse(Message::UI_NPC_CHOOSE_SERVER_NO_SERVER))] : array_map(fn(CloudServer $server) => new MenuOption(Message::parse(Message::UI_NPC_CHOOSE_SERVER_BUTTON, [$server->getName(), count($server->getCloudPlayers()), $server->getCloudServerData()->getMaxPlayers()])), $servers)),
                        function(Player $player, int $data) use($servers): void {
                            /** @var CloudServer $server */
                            if (($server = ($servers[(array_keys($servers)[$data] ?? 0)] ?? null)) instanceof CloudServer) {
                                Message::parse(Message::CONNECT_TO_SERVER, [$server->getName()])->target($player);
                                if (!CloudAPI::getInstance()->transferPlayer($player, $server)) {
                                    Message::parse(Message::CANT_CONNECT, [$server->getName()])->target($player);
                                }
                            }
                        }
                    ));
                }
            }
        }
    }

    public function onJoin(PlayerJoinEvent $e) {
        CloudNPCManager::getInstance()->spawnCloudNPCs();
    }

    public function onInteractWithEntity(DataPacketReceiveEvent $event) {
        $packet = $event->getPacket();
        $origin = $event->getOrigin();
        $player = $origin->getPlayer();

        if ($player instanceof Player) {
            if ($packet instanceof InventoryTransactionPacket) {
                /** @var UseItemOnEntityTransactionData $trData */
                if (($trData = $packet->trData) instanceof UseItemOnEntityTransactionData) {
                    if ($trData->getActionType() == $trData::ACTION_INTERACT) {
                        if (($entity = $player->getWorld()->getEntity($trData->getActorRuntimeId())) !== null) {
                            if (($cloudNPC = CloudNPCManager::getInstance()->getCloudNPC($entity->getPosition())) !== null) {
                                if (!isset(CloudBridge::getInstance()->npcDelay[$player->getName()])) CloudBridge::getInstance()->npcDelay[$player->getName()] = 0;
                                if (Server::getInstance()->getTick() >= CloudBridge::getInstance()->npcDelay[$player->getName()]) {
                                    CloudBridge::getInstance()->npcDelay[$player->getName()] = Server::getInstance()->getTick() + 10;
                                    if (($bestServer = $this->getBestServer($cloudNPC->getTemplate(), $player)) !== null) {
                                        Message::parse(Message::CONNECT_TO_SERVER, [$bestServer->getName()]);
                                        if (!CloudAPI::getInstance()->transferPlayer($player, $bestServer)) {
                                            Message::parse(Message::CANT_CONNECT, [$bestServer->getName()])->target($player);
                                        }
                                    } else Message::parse(Message::NO_SERVER_FOUND)->target($player);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function getBestServer(Template $template, Player $player): ?CloudServer {
        $serverClasses = [];
        $servers = [];
        foreach (CloudAPI::getInstance()->getServersOfTemplate($template) as $server) {
            if ($server->getName() === CloudAPI::getInstance()->getServerName() || $server->getServerStatus() === ServerStatus::FULL() || $server->getServerStatus() === ServerStatus::STOPPING() || $server->getServerStatus() === ServerStatus::IN_GAME() || $server->getTemplate()->isMaintenance()) continue;
            $servers[$server->getName()] = count($server->getCloudPlayers());
            $serverClasses[$server->getName()] = $server;
        }

        arsort($servers);
        return $serverClasses[array_key_first($servers)] ?? null;
    }
}