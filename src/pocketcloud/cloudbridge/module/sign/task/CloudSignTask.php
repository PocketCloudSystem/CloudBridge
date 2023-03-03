<?php

namespace pocketcloud\cloudbridge\module\sign\task;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\api\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\event\CloudSignUpdateEvent;
use pocketcloud\cloudbridge\module\sign\CloudSignManager;
use pocketmine\block\BaseSign;
use pocketmine\block\utils\SignText;
use pocketmine\scheduler\Task;

class CloudSignTask extends Task {

    public function onRun(): void {
        foreach (CloudSignManager::getInstance()->getCloudSigns() as $sign) {
            if ($sign->getPosition()->world !== null && $sign->getPosition()->world->isLoaded()) {
                $block = $sign->getPosition()->getWorld()->getBlock($sign->getPosition()->asVector3());
                if ($block instanceof BaseSign) {
                    if ($sign->hasUsingServer()) {
                        if (CloudAPI::getInstance()->getServerByName($sign->getUsingServer()->getName()) !== null) {
                            if ($sign->getUsingServer()->getServerStatus() === ServerStatus::IN_GAME()) {
                                $ev = new CloudSignUpdateEvent($sign, $sign->getUsingServer(), null);
                                $ev->call();
                                if ($ev->isCancelled()) return;
                                if (CloudSignManager::getInstance()->isUsingServerName($sign->getUsingServer()->getName())) CloudSignManager::getInstance()->removeUsingServerName($sign->getUsingServer()->getName());
                                $sign->setUsingServer($ev->getNewUsingServer());
                                if ($ev->getNewUsingServer() !== null) if (!CloudSignManager::getInstance()->isUsingServerName($ev->getNewUsingServer()->getName())) CloudSignManager::getInstance()->addUsingServerName($ev->getNewUsingServer()->getName(), $sign);
                                $block->setText(new SignText($sign->next()));
                                $block->getPosition()->getWorld()->setBlock($block->getPosition()->asVector3(), $block);
                            } else {
                                if (!CloudSignManager::getInstance()->isUsingServerName($sign->getUsingServer()->getName())) CloudSignManager::getInstance()->addUsingServerName($sign->getUsingServer()->getName(), $sign);
                                $block->setText(new SignText($sign->next()));
                                $block->getPosition()->getWorld()->setBlock($block->getPosition()->asVector3(), $block);
                            }
                        } else {
                            $ev = new CloudSignUpdateEvent($sign, $sign->getUsingServer(), null);
                            $ev->call();
                            if ($ev->isCancelled()) return;
                            if (CloudSignManager::getInstance()->isUsingServerName($sign->getUsingServer()->getName())) CloudSignManager::getInstance()->removeUsingServerName($sign->getUsingServer()->getName());
                            $sign->setUsingServer($ev->getNewUsingServer());
                            if ($ev->getNewUsingServer() !== null) if (!CloudSignManager::getInstance()->isUsingServerName($ev->getNewUsingServer()->getName())) CloudSignManager::getInstance()->addUsingServerName($ev->getNewUsingServer()->getName(), $sign);
                            $block->setText(new SignText($sign->next()));
                            $block->getPosition()->getWorld()->setBlock($block->getPosition()->asVector3(), $block);
                        }
                    } else {
                        $freeServer = $this->getFreeServer($sign->getTemplate());
                        if ($freeServer !== null) {
                            $ev = new CloudSignUpdateEvent($sign, $sign->getUsingServer(), $freeServer);
                            $ev->call();
                            if ($ev->isCancelled()) return;
                            if ($ev->getNewUsingServer() !== null) CloudSignManager::getInstance()->addUsingServerName($ev->getNewUsingServer()->getName(), $sign);
                            $sign->setUsingServer($ev->getNewUsingServer());
                            $block->setText(new SignText($sign->next()));
                            $block->getPosition()->getWorld()->setBlock($block->getPosition()->asVector3(), $block);
                        } else {
                            $sign->setUsingServer(null);
                            $block->setText(new SignText($sign->next()));
                            $block->getPosition()->getWorld()->setBlock($block->getPosition()->asVector3(), $block);
                        }
                    }
                }
            }
        }
    }

    private function getFreeServer(Template $template): ?CloudServer {
        foreach (CloudAPI::getInstance()->getServersOfTemplate($template) as $server) {
            if ($server->getServerStatus() === ServerStatus::ONLINE() && !$server->getTemplate()->isMaintenance()) {
                if (!CloudSignManager::getInstance()->isUsingServerName($server->getName())) return $server;
            }
        }
        return null;
    }
}