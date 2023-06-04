<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\registry\Registry;
use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;
use pocketcloud\cloudbridge\api\server\CloudServer;

class ServerSyncPacket extends CloudPacket {

    public function __construct(
        private ?CloudServer $server = null,
        private bool $removal = false
    ) {}

    public function encodePayload(PacketData $packetData) {
        $packetData->writeServer($this->server);
        $packetData->write($this->removal);
    }

    public function decodePayload(PacketData $packetData) {
        $this->server = $packetData->readServer();
        $this->removal = $packetData->readBool();
    }

    public function getServer(): ?CloudServer {
        return $this->server;
    }

    public function isRemoval(): bool {
        return $this->removal;
    }

    public function handle() {
        if (CloudAPI::getInstance()->getServerByName($this->server->getName()) === null) {
            if (!$this->removal) Registry::registerServer($this->server);
        } else {
            if ($this->removal) {
                Registry::unregisterServer($this->server->getName());
            } else Registry::updateServer($this->server->getName(), $this->server->getServerStatus());
        }
    }
}