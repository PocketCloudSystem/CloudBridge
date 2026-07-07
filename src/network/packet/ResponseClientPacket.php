<?php

namespace pocketcloud\cloud\bridge\network\packet;

use pocketcloud\cloud\bridge\network\packet\data\PacketData;

/**
 * A different version from the regular ResponsePacket
 * This logic is reversed, means the sub-servers sends this ResponseClientPacket in response to the RequestClientPacket
 * @see RequestClientPacket
 * @see ResponseClientPacket
 */
abstract class ResponseClientPacket extends CloudPacket implements CloudboundPacket {

    private string $requestId = "";

    final public function encode(PacketData $packetData): void {
        parent::encode($packetData);
        $packetData->write($this->requestId);
    }

    final public function decode(PacketData $packetData): void {
        parent::decode($packetData);
        $this->requestId = $packetData->readString();
    }

    public function getRequestId(): string {
        return $this->requestId;
    }

    public function setRequestId(string $requestId): self {
        $this->requestId = $requestId;
        return $this;
    }

    final public function decodePayload(PacketData $packetData): void {}

    final public function handle(): void {}
}