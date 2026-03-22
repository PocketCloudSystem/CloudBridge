<?php

namespace pocketcloud\cloud\bridge\module\impl\sign\listener;

use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\sign\CloudSign;
use pocketcloud\cloud\bridge\module\impl\sign\CloudSignModule;
use pocketcloud\cloud\bridge\player\PlayerSession;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

final class SignListener implements Listener {

    public function onChange(SignChangeEvent $event): void {
        if (CloudSignModule::get()->getCloudSign($event->getSign()->getPosition()) !== null) {
            $event->cancel();
            return;
        }

        if ($event->getNewText()->getLine(0) == "[PocketCloud]") {
            if ($event->getPlayer()->hasPermission("pocketcloud.cloudsign.add")) {
                if (($template = TemplateProvider::provider()->get($event->getNewText()->getLine(1))) !== null) {
                    CloudSignModule::get()->addCloudSign(new CloudSign($template, $event->getSign()->getPosition()));
                }
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event): void {
        if ($event->getAction() === $event::LEFT_CLICK_BLOCK) return;
        $player = $event->getPlayer();
        $session = PlayerSession::get($player);
        if (($sign = CloudSignModule::get()->getCloudSign($event->getBlock()->getPosition())) !== null) {
            $event->cancel();
            if ($session->isOnSignInteractionCooldown()) return;
            $session->setOnsignInteractionCooldown();
            if ($sign->hasUsingServer() && !$sign->getUsingServer()->getTemplate()->isMaintenance()) {
                if (CloudServerProvider::provider()->current()?->getName() == $sign->getUsingServer()->getName()) {
                    $player->sendMessage(LanguageKey::INGAME_SERVER_ALREADY_CONNECTED()->translate([$sign->getUsingServerName()]));
                } else {
                    $player->sendMessage(LanguageKey::INGAME_SERVER_CONNECT()->translate([$sign->getUsingServerName()]));
                    if (!CloudPlayerProvider::provider()->transfer($player, $sign->getUsingServer())) {
                        $player->sendMessage(LanguageKey::INGAME_SERVER_CONNECT_FAILED()->translate([$sign->getUsingServerName()]));
                    }
                }
            }
        }
    }

    public function onBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        if (($sign = CloudSignModule::get()->getCloudSign($event->getBlock()->getPosition())) !== null) {
            if ($player->hasPermission("pocketcloud.cloudsign.remove")) {
                CloudSignModule::get()->removeCloudSign($sign);
            } else $event->cancel();
        }
    }
}