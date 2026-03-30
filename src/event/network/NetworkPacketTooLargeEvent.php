<?php

namespace pocketcloud\cloud\bridge\event\network;

use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;

class NetworkPacketTooLargeEvent extends NetworkEvent {

    public function __construct(
        Network $network,
        protected readonly CloudboundPacket $packet,
        protected readonly int $size,
        protected readonly string $buffer
    ) {
        parent::__construct($network);
    }

    public function getPacket(): CloudboundPacket {
        return $this->packet;
    }

    public function getSize(): int {
        return $this->size;
    }

    public function getBuffer(): string {
        return $this->buffer;
    }
}