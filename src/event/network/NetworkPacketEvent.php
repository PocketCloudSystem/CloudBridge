<?php

namespace pocketcloud\cloud\bridge\event\network;

use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\Packet;
use pocketcloud\cloud\bridge\util\net\Address;

abstract class NetworkPacketEvent extends NetworkEvent {

    public function __construct(
        Network $network,
        protected readonly Address $sender,
        protected readonly Packet $packet
    ) {
        parent::__construct($network);
    }

    public function getSender(): Address {
        return $this->sender;
    }

    public function getPacket(): Packet {
        return $this->packet;
    }
}