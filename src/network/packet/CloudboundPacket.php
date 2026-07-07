<?php

namespace pocketcloud\cloud\bridge\network\packet;

use pocketcloud\cloud\bridge\network\packet\data\PacketData;

/**
 *  CloudboundPacket -> Cloud is the receiver (only decode), Server (Client) is the sender
 */
interface CloudboundPacket extends Packet {

    public function encodePayload(PacketData $packetData): void;
}