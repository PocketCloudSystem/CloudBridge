<?php

namespace pocketcloud\cloudbridge\network\packet\impl\request;

use pocketcloud\cloudbridge\network\packet\RequestPacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class CloudServerStopRequestPacket extends RequestPacket {

    public function __construct(private string $server = "") {
        parent::__construct();
    }

    public function encodePayload(PacketData $packetData) {
        $packetData->write($this->server);
    }

    public function decodePayload(PacketData $packetData) {
        $this->server = $packetData->readString();
    }

    public function getServer(): string {
        return $this->server;
    }
}