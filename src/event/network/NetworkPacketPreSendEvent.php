<?php

namespace pocketcloud\cloud\bridge\event\network;

use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class NetworkPacketPreSendEvent extends NetworkEvent implements Cancellable {
    use CancellableTrait;

    public function __construct(
        Network $network,
        protected readonly CloudboundPacket $packet
    ) {
        parent::__construct($network);
    }

    public function getPacket(): CloudboundPacket {
        return $this->packet;
    }
}