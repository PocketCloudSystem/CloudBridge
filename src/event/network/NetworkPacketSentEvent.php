<?php

namespace pocketcloud\cloud\bridge\event\network;

use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;

class NetworkPacketSentEvent extends NetworkEvent {

    public function __construct(
        Network $network,
        protected readonly CloudboundPacket $packet,
        protected readonly bool $success
    ) {
        parent::__construct($network);
    }

    public function getPacket(): CloudboundPacket {
        return $this->packet;
    }

    public function isSuccess(): bool {
        return $this->success;
    }
}