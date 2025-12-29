<?php

namespace pocketcloud\cloud\bridge\network\packet;

use pocketcloud\cloud\bridge\network\packet\util\PacketData;

abstract class ResponsePacket extends CloudPacket implements ClientboundPacket {

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