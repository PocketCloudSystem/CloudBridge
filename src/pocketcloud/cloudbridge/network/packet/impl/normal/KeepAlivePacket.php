<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\CloudPacket;

class KeepAlivePacket extends CloudPacket {

    public function handle(): void {
        CloudBridge::getInstance()->lastKeepALiveCheck = time();
        Network::getInstance()->sendPacket(new KeepALivePacket());
    }
}