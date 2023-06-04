<?php

namespace pocketcloud\cloudbridge\module\sign\listener;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\sign\CloudSign;
use pocketcloud\cloudbridge\module\sign\CloudSignManager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Server;

class SignListener implements Listener {

    public function onChange(SignChangeEvent $event) {
        if (CloudSignManager::isEnabled()) {
            if ($event->getNewText()->getLine(0) == "[PocketCloud]") {
                if ($event->getPlayer()->hasPermission("pocketcloud.cloudsign.add")) {
                    if (($template = CloudAPI::getInstance()->getTemplateByName($event->getNewText()->getLine(1))) !== null) {
                        CloudSignManager::getInstance()->addCloudSign(new CloudSign($template, $event->getSign()->getPosition()));
                    }
                }
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event) {
        if (CloudSignManager::isEnabled()) {
            if ($event->getAction() === $event::LEFT_CLICK_BLOCK) return;
            if (($sign = CloudSignManager::getInstance()->getCloudSign($event->getBlock()->getPosition())) !== null) {
                if (!isset(CloudBridge::getInstance()->signDelay[$event->getPlayer()->getName()])) CloudBridge::getInstance()->signDelay[$event->getPlayer()->getName()] = 0;
                if (Server::getInstance()->getTick() >= CloudBridge::getInstance()->signDelay[$event->getPlayer()->getName()]) {
                    CloudBridge::getInstance()->signDelay[$event->getPlayer()->getName()] = Server::getInstance()->getTick() + 10;
                    if ($sign->hasUsingServer() && !$sign->getUsingServer()->getTemplate()->isMaintenance()) {
                        if (CloudAPI::getInstance()->getCurrentServer()?->getName() == $sign->getUsingServer()->getName()) {
                            $event->getPlayer()->sendMessage(Language::current()->translate("inGame.server.already.connected", $sign->getUsingServer()->getName()));
                        } else {
                            $event->getPlayer()->sendMessage(Language::current()->translate("inGame.server.connect", $sign->getUsingServer()->getName()));
                            if (!CloudAPI::getInstance()->transferPlayer($event->getPlayer(), $sign->getUsingServer())) {
                                $event->getPlayer()->sendMessage(Language::current()->translate("inGame.server.connect.failed", $sign->getUsingServer()->getName()));
                            }
                        }
                    }
                }
            }
        }
    }

    public function onBreak(BlockBreakEvent $event) {
        if (CloudSignManager::isEnabled()) {
            if (($sign = CloudSignManager::getInstance()->getCloudSign($event->getBlock()->getPosition())) !== null) {
                if ($event->getPlayer()->hasPermission("pocketcloud.cloudsign.remove")) {
                    CloudSignManager::getInstance()->removeCloudSign($sign);
                } else $event->cancel();
            }
        }
    }
}