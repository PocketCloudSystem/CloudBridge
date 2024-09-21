<?php

namespace pocketcloud\cloudbridge\module\sign\task;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\object\server\CloudServer;
use pocketcloud\cloudbridge\api\object\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\object\template\Template;
use pocketcloud\cloudbridge\event\sign\CloudSignUpdateEvent;
use pocketcloud\cloudbridge\module\sign\CloudSignModule;
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
        foreach (CloudAPI::serverProvider()->getServersByTemplate($template) as $server) {
            if ($server->getServerStatus() === ServerStatus::ONLINE() && !$server->getTemplate()->isMaintenance()) {
                if (!CloudSignModule::get()->isUsingServerName($server->getName())) return $server;
            }
        }
        return null;
    }
}