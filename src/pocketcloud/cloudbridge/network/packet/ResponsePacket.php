<?php

namespace pocketcloud\cloudbridge\network\packet;

use pocketcloud\cloudbridge\network\packet\utils\PacketData;

abstract class ResponsePacket extends CloudPacket {

    private string $requestId = "";

    public function encode(PacketData $packetData): void {
        parent::encode($packetData);
        $packetData->write($this->requestId);
    }

    public function decode(PacketData $packetData): void {
        parent::decode($packetData);
        $this->requestId = $packetData->readString();
    }

    public function getRequestId(): string {
        return $this->requestId;
    }
}