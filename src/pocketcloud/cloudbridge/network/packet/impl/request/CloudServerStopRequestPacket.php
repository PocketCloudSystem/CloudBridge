<?php

namespace pocketcloud\cloudbridge\network\packet\impl\request;

use pocketcloud\cloudbridge\network\packet\RequestPacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class CloudServerStopRequestPacket extends RequestPacket {

    public function __construct(private string $server = "") {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->server);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->server = $packetData->readString();
    }

    public function getServer(): string {
        return $this->server;
    }
}