<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\normal;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;

final class KeepAlivePacket extends CloudPacket {

    public function handle(): void {
        CloudBridge::getInstance()->lastKeepALiveCheck = time();
        Network::getInstance()->sendPacket(new KeepALivePacket());
    }
}