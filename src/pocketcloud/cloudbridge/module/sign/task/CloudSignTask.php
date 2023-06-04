<?php

namespace pocketcloud\cloudbridge\module\sign\task;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\api\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\event\sign\CloudSignUpdateEvent;
use pocketcloud\cloudbridge\module\sign\CloudSignManager;
use pocketmine\block\BaseSign;
use pocketmine\block\utils\SignText;
use pocketmine\scheduler\Task;

class CloudSignTask extends Task {

    public function onRun(): void {
        if (!CloudSignManager::isEnabled()) {
            $this->getHandler()->cancel();
            return;
        }
        foreach (CloudSignManager::getInstance()->getCloudSigns() as $sign) {
            if ($sign->getPosition()->world !== null && $sign->getPosition()->world->isLoaded()) {
                $block = $sign->getPosition()->getWorld()->getBlock($sign->getPosition()->asVector3());
                if ($block instanceof BaseSign) {
                    if ($sign->hasUsingServer()) {
                        if ($sign->getUsingServer()->getServerStatus() === ServerStatus::IN_GAME()) {
                            ($ev = new CloudSignUpdateEvent($sign, $sign->getUsingServer(), null))->call();
                            if (!$ev->isCancelled()) {
                                $sign->setUsingServer($ev->getNewUsingServer());
                                if ($ev->getNewUsingServer() !== null) CloudSignManager::getInstance()->addUsingServerName($ev->getNewUsingServer()->getName(), $sign);
                                $block->setText(new SignText($sign->next()));
                                $block->getPosition()->getWorld()->setBlock($block->getPosition(), $block);
                            }
                        } else {
                            $block->setText(new SignText($sign->next()));
                            $block->getPosition()->getWorld()->setBlock($block->getPosition(), $block);
                        }
                    } else {
                        if ($sign->getUsingServer() !== null) CloudSignManager::getInstance()->removeUsingServerName($sign->getUsingServer()->getName());
                        $freeServer = $this->getFreeServer($sign->getTemplate());
                        if ($freeServer !== null) {
                            ($ev = new CloudSignUpdateEvent($sign, $sign->getUsingServer(), $freeServer))->call();
                            if ($ev->isCancelled()) return;
                            CloudSignManager::getInstance()->addUsingServerName($ev->getNewUsingServer()->getName(), $sign);
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
        foreach (CloudAPI::getInstance()->getServersByTemplate($template) as $server) {
            if ($server->getServerStatus() === ServerStatus::ONLINE() && !$server->getTemplate()->isMaintenance()) {
                if (!CloudSignManager::getInstance()->isUsingServerName($server->getName())) return $server;
            }
        }
        return null;
    }
}