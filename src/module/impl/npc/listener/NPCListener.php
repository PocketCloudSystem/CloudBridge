<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\listener;

use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPC;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use r3pt1s\forms\builder\MenuFormBuilder;
use r3pt1s\forms\element\menu\MenuOption;

final class NPCListener implements Listener {

    public function onMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        foreach (CloudNPCModule::get()->getAll() as $cloudNPC) {
            if (!$cloudNPC->isVisibleTo($player)) continue;
            if ($cloudNPC->getPosition()->distanceSquared($player->getPosition()) > 9) continue;
            $horizontal = sqrt(
                ($player->getPosition()->x - $cloudNPC->getPosition()->x) ** 2 +
                ($player->getPosition()->z - $cloudNPC->getLocation()->z) ** 2
            );

            $vertical = $player->getPosition()->y - $cloudNPC->getLocation()->getY();
            $pitch = -atan2($vertical, $horizontal) / M_PI * 180;

            $xDist = $player->getPosition()->x - $cloudNPC->getLocation()->x;
            $zDist = $player->getPosition()->z - $cloudNPC->getLocation()->z;
            $yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
            if ($yaw < 0) $yaw += 360.0;

            $player->getNetworkSession()->sendDataPacket(
                MoveActorAbsolutePacket::create(
                    $cloudNPC->getId(),
                    Position::fromObject($cloudNPC->getOffsetPosition($cloudNPC->getPosition()), $cloudNPC->getWorld()),
                    $pitch, $yaw, $yaw, 0
                )
            );
        }
    }

    public function onPlayerEntityInteract(PlayerEntityInteractEvent $event): void {
        $player = $event->getPlayer();
        $cloudNPC = $event->getEntity();

        if (!$cloudNPC instanceof CloudNPC) return;

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
                ->onSubmit(function (Player $player, int $index) use($templates): void {
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
