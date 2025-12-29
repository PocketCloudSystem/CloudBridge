<?php

namespace pocketcloud\cloud\bridge\network\packet;

use pocketcloud\cloud\bridge\network\packet\util\PacketData;

/**
 *  ClientboundPacket -> Server (Client) is the receiver, Cloud is the sender (only encode)
 */
interface ClientboundPacket extends Packet {

    public function decodePayload(PacketData $packetData): void;
}