<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\listener;

use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\server\util\ServerStatus;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPC;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
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
use r3pt1s\forms\builder\MenuFormBuilder;
use r3pt1s\forms\element\menu\MenuOption;

final class NPCListener implements Listener {

    public function onMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();

        foreach (array_filter(CloudNPCModule::get()->getAll(), fn(CloudNPC $npc) => $npc->isHeadRotation()) as $cloudNPC) {
            if (($entity = $cloudNPC->getEntity()) === null) continue;

            if ($entity->getPosition()->distance($player->getPosition()) > 9) continue;

            $horizontal = sqrt(
                ($player->getPosition()->x - $entity->getPosition()->x) ** 2 +
                ($player->getPosition()->z - $entity->getLocation()->z) ** 2
            );
            $vertical = $player->getPosition()->y - $entity->getLocation()->getY();
            $pitch = -atan2($vertical, $horizontal) / M_PI * 180;

            $xDist = $player->getPosition()->x - $entity->getLocation()->x;
            $zDist = $player->getPosition()->z - $entity->getLocation()->z;
            $yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
            if ($yaw < 0) $yaw += 360.0;

            $player->getNetworkSession()->sendDataPacket(
                MoveActorAbsolutePacket::create(
                    $entity->getId(),
                    Position::fromObject($entity->getOffsetPosition($entity->getPosition()), $entity->getWorld()),
                    $pitch, $yaw, $yaw, 0
                )
            );
        }
    }

    public function onHit(EntityDamageByEntityEvent $event): void {
        $entity = $event->getEntity();
        $damager = $event->getDamager();

        $cloudNPC = CloudNPCModule::get()->getCloudNPC($entity->getPosition());
        if ($cloudNPC === null) return;

        $event->cancel();
        if (!$damager instanceof Player) return;

        if (isset(CloudNPCModule::get()->npcDetection[$damager->getName()])) {
            unset(CloudNPCModule::get()->npcDetection[$damager->getName()]);
            if (CloudNPCModule::get()->removeCloudNPC($cloudNPC)) {
                $damager->sendMessage(LanguageKey::INGAME_CLOUDNPC_REMOVED());
            } else {
                $damager->sendMessage(LanguageKey::INGAME_PREFIX() . "§cAn error occurred while removing the NPC.");
            }
            return;
        }

        $servers = array_values(array_filter(
            $cloudNPC->getServers(),
            fn(CloudServer $s) => $s->getName() !== CloudEnvironmentConfig::getServerName() &&
                $s->getServerStatus() === ServerStatus::ONLINE &&
                !($s->getTemplate()->isMaintenance() && !$damager->hasPermission("pocketcloud.bypass.maintenance"))
        ));

        $name = $cloudNPC->hasTemplateGroup() ? $cloudNPC->getTemplate()->getDisplayName() : $cloudNPC->getTemplate()->getName();

        $options = count($servers) === 0
            ? [new MenuOption(LanguageKey::INGAME_UI_CLOUDNPC_CHOOSE_SERVER_NO_SERVER())]
            : array_map(
                fn(CloudServer $s) => new MenuOption(LanguageKey::INGAME_UI_CLOUDNPC_CHOOSE_SERVER_BUTTON_SERVER()->translate(
                    [
                        $s->getName(),
                        count($s->getCloudPlayers()),
                        $s->getCloudServerData()->getMaxPlayers()
                    ]
                )),
                $servers
            );

        $damager->sendForm(MenuFormBuilder::create(LanguageKey::INGAME_UI_CLOUDNPC_CHOOSE_SERVER_TITLE()->translate([$name]), LanguageKey::INGAME_UI_CLOUDNPC_CHOOSE_SERVER_TEXT()->translate([count($servers), $name]))
            ->elements($options)
            ->onSubmit(function (Player $player, int $index, MenuOption $option) use($servers): void {
                $server = $servers[$index] ?? null;
                if (!$server instanceof CloudServer) return;

                $player->sendMessage(LanguageKey::INGAME_SERVER_CONNECT()->translate([$server->getName()]));
                if (!CloudPlayerProvider::provider()->transfer($player, $server)) {
                    $player->sendMessage(LanguageKey::INGAME_SERVER_CONNECT_FAILED()->translate([$server->getName()]));
                }
            })
            ->build()
        );
    }

    public function onJoin(PlayerJoinEvent $event): void {
        CloudNPCModule::get()->spawnAll();
    }

    public function onInteractWithEntity(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        $player = $event->getOrigin()->getPlayer();
        if (!$player instanceof Player) return;

        if (!$packet instanceof InventoryTransactionPacket) return;
        /** @var UseItemOnEntityTransactionData $trData */
        $trData = $packet->trData;
        if (!$trData instanceof UseItemOnEntityTransactionData) return;
        if ($trData->getActionType() === UseItemOnEntityTransactionData::ACTION_ATTACK) return;

        $entity = $player->getWorld()->getEntity($trData->getActorRuntimeId());
        $cloudNPC = $entity !== null ? CloudNPCModule::get()->getCloudNPC($entity->getPosition()) : null;
        if ($cloudNPC === null) return;

        if (!isset(CloudNPCModule::get()->npcDelay[$player->getName()])) CloudNPCModule::get()->npcDelay[$player->getName()] = 0;
        if (Server::getInstance()->getTick() < CloudNPCModule::get()->npcDelay[$player->getName()]) return;
        CloudNPCModule::get()->npcDelay[$player->getName()] = Server::getInstance()->getTick() + 10;

        if ($cloudNPC->hasTemplateGroup()) {
            $templates = $cloudNPC->getTemplate()->getTemplates();
            $groupName = $cloudNPC->getTemplate()->getDisplayName();

            $player->sendForm(MenuFormBuilder::create(LanguageKey::INGAME_UI_CLOUDNPC_CHOOSE_SERVER_TITLE()->translate([$groupName]), LanguageKey::INGAME_UI_CLOUDNPC_CHOOSE_SERVER_TEXT()->translate([$groupName]))
                ->elements(array_map(
                    fn(string $tName) => new MenuOption(LanguageKey::INGAME_UI_CLOUDNPC_CHOOSE_TEMPLATE_BUTTON_TEMPLATE()->translate(
                        [
                            $tName,
                            count(CloudPlayerProvider::provider()->getAll($t = TemplateProvider::provider()->get($tName))),
                            $t?->getMaxPlayerCount() ?? 0
                        ]
                    )),
                    $templates
                ))
                ->onSubmit(function (Player $player, int $index, MenuOption $option) use($templates): void {
                    $templateName = $templates[$index] ?? null;
                    $template = $templateName !== null ? TemplateProvider::provider()->get($templateName) : null;
                    if ($template === null) {
                        $player->sendMessage(LanguageKey::INGAME_CLOUDNPC_QUICKJOIN_NO_SERVER());
                        return;
                    }

                    $best = CloudServerProvider::provider()->freeServer($template, [CloudEnvironmentConfig::getServerName()]);
                    if ($best !== null) {
                        $player->sendMessage(LanguageKey::INGAME_SERVER_CONNECT()->translate([$best->getName()]));
                        if (!CloudPlayerProvider::provider()->transfer($player, $best)) {
                            $player->sendMessage(LanguageKey::INGAME_SERVER_CONNECT_FAILED()->translate([$best->getName()]));
                        }
                    } else {
                        $player->sendMessage(LanguageKey::INGAME_CLOUDNPC_QUICKJOIN_NO_SERVER());
                    }
                })
                ->build()
            );

            return;
        }

        $best = CloudServerProvider::provider()->freeServer($cloudNPC->getTemplate(), [CloudEnvironmentConfig::getServerName()]);
        if ($best !== null) {
            $player->sendMessage(LanguageKey::INGAME_SERVER_CONNECT()->translate([$best->getName()]));
            if (!CloudPlayerProvider::provider()->transfer($player, $best)) {
                $player->sendMessage(LanguageKey::INGAME_SERVER_CONNECT_FAILED()->translate([$best->getName()]));
            }
        } else {
            $player->sendMessage(LanguageKey::INGAME_CLOUDNPC_QUICKJOIN_NO_SERVER());
        }
    }
}
