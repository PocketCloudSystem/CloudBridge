<?php

namespace pocketcloud\cloudbridge\module\sign\task;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\api\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\event\sign\CloudSignUpdateEvent;
use pocketcloud\cloudbridge\module\sign\CloudSignModule;
use pocketmine\block\BaseSign;
use pocketmine\block\utils\SignText;
use pocketmine\scheduler\Task;

class CloudSignTask extends Task {

    public function onRun(): void {
        foreach (CloudSignModule::get()->getCloudSigns() as $sign) {
            if ($sign->getPosition()->isValid()) {
                $block = $sign->getPosition()->getWorld()->getBlock($sign->getPosition()->asVector3());
                if ($block instanceof BaseSign) {
                    if ($sign->hasUsingServer()) {
                        if ($sign->getUsingServer()->getServerStatus() === ServerStatus::IN_GAME()) {
                            ($ev = new CloudSignUpdateEvent($sign, $sign->getUsingServerName(), null))->call();
                            if (!$ev->isCancelled()) {
                                $sign->setUsingServer($ev->getNewUsingServer());
                                if ($ev->getNewUsingServer() !== null) CloudSignModule::get()->addUsingServerName($ev->getNewUsingServer(), $sign);
                                CloudSignModule::get()->removeUsingServerName($ev->getOldUsingServer());
                                $block->setText(new SignText($sign->next()));
                                $block->getPosition()->getWorld()->setBlock($block->getPosition(), $block);
                            }
                        } else {
                            $block->setText(new SignText($sign->next()));
                            $block->getPosition()->getWorld()->setBlock($block->getPosition(), $block);
                        }
                    } else {
                        if ($sign->getUsingServerName() !== null) {
                            ($ev = new CloudSignUpdateEvent($sign, $sign->getUsingServerName(), null))->call();
                            if (!$ev->isCancelled()) {
                                CloudSignModule::get()->removeUsingServerName($sign->getUsingServerName());
                                $block->setText(new SignText($sign->next()));
                                $block->getPosition()->getWorld()->setBlock($block->getPosition(), $block);
                            }
                        } else {
                            $freeServer = $this->getFreeServer($sign->getTemplate());
                            if ($freeServer !== null) {
                                ($ev = new CloudSignUpdateEvent($sign, $sign->getUsingServerName(), $freeServer->getName()))->call();
                                if ($ev->isCancelled()) return;
                                CloudSignModule::get()->addUsingServerName($ev->getNewUsingServer(), $sign);
                                $sign->setUsingServer($ev->getNewUsingServer());
                            }

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
                if (!CloudSignModule::get()->isUsingServerName($server->getName())) return $server;
            }
        }
        return null;
    }
}