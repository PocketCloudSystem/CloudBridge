<?php

namespace pocketcloud\cloud\bridge\event\impl\network;

use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\util\net\Address;

class NetworkPacketSendEvent extends NetworkEvent {

    public function __construct(
        private readonly CloudboundPacket $packet,
        Address $sender,
        private readonly bool $success
    ) {
        parent::__construct($sender);
    }

    public function getPacket(): CloudboundPacket {
        return $this->packet;
    }

    public function isSuccess(): bool {
        return $this->success;
    }
}