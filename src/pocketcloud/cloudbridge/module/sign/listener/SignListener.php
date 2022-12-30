<?php

namespace pocketcloud\cloudbridge\module\sign\listener;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\module\sign\CloudSign;
use pocketcloud\cloudbridge\module\sign\CloudSignManager;
use pocketcloud\cloudbridge\utils\Message;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Server;
use pocketmine\utils\Internet;

class SignListener implements Listener {

    public function onChange(SignChangeEvent $event) {
        if ($event->getNewText()->getLine(0) == "[PocketCloud]") {
            if ($event->getPlayer()->hasPermission("pocketcloud.cloudsign.add")) {
                if (($template = CloudAPI::getInstance()->getTemplateByName($event->getNewText()->getLine(1))) !== null) {
                    CloudSignManager::getInstance()->addCloudSign(new CloudSign($template, $event->getSign()->getPosition()));
                }
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event) {
        if ($event->getAction() === $event::LEFT_CLICK_BLOCK) return;
        if (($sign = CloudSignManager::getInstance()->getCloudSign($event->getBlock()->getPosition())) !== null) {
            if (!isset(CloudBridge::getInstance()->signDelay[$event->getPlayer()->getName()])) CloudBridge::getInstance()->signDelay[$event->getPlayer()->getName()] = 0;

            if (Server::getInstance()->getTick() >= CloudBridge::getInstance()->signDelay[$event->getPlayer()->getName()]) {
                CloudBridge::getInstance()->signDelay[$event->getPlayer()->getName()] = Server::getInstance()->getTick() + 10;
                if ($sign->hasUsingServer() && !$sign->getUsingServer()->getTemplate()->isMaintenance()) {
                    if (CloudAPI::getInstance()->getCurrentServer()?->getName() == $sign->getUsingServer()->getName()) {
                        Message::parse(Message::ALREADY_CONNECTED, [$sign->getUsingServer()->getName()])->target($event->getPlayer());
                    } else {
                        Message::parse(Message::CONNECT_TO_SERVER, [$sign->getUsingServer()->getName()])->target($event->getPlayer());
                        if (($cloudPlayer = CloudAPI::getInstance()->getPlayerByName($event->getPlayer()->getName())) !== null) {
                            if (!CloudAPI::getInstance()->transferPlayer($event->getPlayer(), $sign->getUsingServer(), $cloudPlayer)) {
                                Message::parse(Message::CANT_CONNECT, [$sign->getUsingServer()->getName()])->target($event->getPlayer());
                            }
                        } else $event->getPlayer()->transfer(Internet::getInternalIP(), $sign->getUsingServer()->getCloudServerData()->getPort());
                    }
                }
            }
        }
    }

    public function onBreak(BlockBreakEvent $event) {
        if (($sign = CloudSignManager::getInstance()->getCloudSign($event->getBlock()->getPosition())) !== null) {
            if ($event->getPlayer()->hasPermission("pocketcloud.cloudsign.remove")) {
                CloudSignManager::getInstance()->removeCloudSign($sign);
            } else $event->cancel();
        }
    }
}