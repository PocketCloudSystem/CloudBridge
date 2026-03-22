<?php

namespace pocketcloud\cloud\bridge\event\network;


use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\Packet;
use pocketcloud\cloud\bridge\util\net\Address;

class NetworkPacketTooLargeEvent extends NetworkPacketEvent {

    public function __construct(
        Network $network,
        Address $sender,
        CloudboundPacket $packet,
        protected readonly int $size,
        protected readonly string $buffer
    ) {
        parent::__construct($network, $sender, $packet);
    }

    /** @return CloudboundPacket */
    public function getPacket(): Packet {
        return $this->packet;
    }

    public function getSize(): int {
        return $this->size;
    }

    public function getBuffer(): string {
        return $this->buffer;
    }
}