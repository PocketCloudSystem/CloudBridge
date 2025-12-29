<?php

namespace pocketcloud\cloud\bridge\event\network;

use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\util\net\Address;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class NetworkPacketReceiveEvent extends NetworkEvent implements Cancellable {
    use CancellableTrait;

    public function __construct(
        private readonly ClientboundPacket $packet,
        Address $sender
    ) {
        parent::__construct($sender);
    }

    public function getPacket(): ClientboundPacket {
        return $this->packet;
    }
}