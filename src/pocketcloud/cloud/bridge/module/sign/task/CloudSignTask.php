<?php

namespace pocketcloud\cloud\bridge\module\sign\task;

use pocketcloud\cloud\bridge\api\CloudAPI;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\server\status\ServerStatus;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\event\sign\CloudSignUpdateEvent;
use pocketcloud\cloud\bridge\module\sign\CloudSignModule;
use pocketmine\block\BaseSign;
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
                            if (!$ev->isCancelled()) $sign->onRemoveServer();
                        }
                    } else {
                        if ($sign->isHoldingServer()) {
                            ($ev = new CloudSignUpdateEvent($sign, $sign->getUsingServerName(), null))->call();
                            if (!$ev->isCancelled()) $sign->onRemoveServer();
                        } else {
                            $freeServer = $this->getFreeServer($sign->getTemplate());
                            if ($freeServer !== null) {
                                ($ev = new CloudSignUpdateEvent($sign, null, $freeServer->getName()))->call();
                                if (!$ev->isCancelled()) $sign->onSetServer($ev->getNewUsingServer());
                            }
                        }
                    }

                    $sign->reloadSign($block);
                }
            }
        }
    }

    private function getFreeServer(Template $template): ?CloudServer {
        foreach (CloudAPI::servers()->getAll($template) as $server) {
            if ($server->getServerStatus() === ServerStatus::ONLINE() && !$server->getTemplate()->isMaintenance()) {
                if (!CloudSignModule::get()->isUsingServerName($server->getName())) return $server;
            }
        }
        return null;
    }
}