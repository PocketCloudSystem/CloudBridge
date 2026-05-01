<?php

namespace pocketcloud\cloud\bridge\exception;

use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;

class PacketTooLargeException extends PacketException {

    public function __construct(CloudboundPacket $packet, int $length, int $limit) {
        parent::__construct("Packet {$packet->getName()} is too large: {$length} > {$limit}");
    }
}