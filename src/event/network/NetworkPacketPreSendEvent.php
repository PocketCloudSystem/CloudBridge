<?php

namespace pocketcloud\cloud\bridge\event\network;

use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\util\net\Address;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class NetworkPacketPreSendEvent extends NetworkEvent implements Cancellable {
    use CancellableTrait;

    public function __construct(
        private readonly CloudboundPacket $packet,
        Address $sender
    ) {
        parent::__construct($sender);
    }

    public function getPacket(): CloudboundPacket {
        return $this->packet;
    }
}