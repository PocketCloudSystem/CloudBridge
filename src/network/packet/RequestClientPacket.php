<?php

namespace pocketcloud\cloud\bridge\network\packet;

use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

/**
 * A different version from the regular RequestClientPacket
 * This logic is reversed, means the cloud sends this RequestClientPacket and the sub-servers answer via ResponseClientPacket
 * @see RequestClientPacket
 * @see ResponseClientPacket
 */
abstract class RequestClientPacket extends CloudPacket implements ClientboundPacket {

    private string $requestId = "";

    final public function encode(PacketData $packetData): void {
        parent::encode($packetData);
        $packetData->write($this->requestId);
    }

    final public function decode(PacketData $packetData): void {
        parent::decode($packetData);
        $this->requestId = $packetData->readString();
    }

    final public function encodePayload(PacketData $packetData): void {}

    public function sendResponse(ResponseClientPacket $packet): bool {
        return Network::getInstance()->sendPacket($packet->setRequestId($this->requestId));
    }

    public function getRequestId(): string {
        return $this->requestId;
    }
}