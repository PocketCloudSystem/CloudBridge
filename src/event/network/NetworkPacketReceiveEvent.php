<?php

namespace pocketcloud\cloud\bridge\event\network;

use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\Packet;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class NetworkPacketReceiveEvent extends NetworkEvent implements Cancellable {
    use CancellableTrait;

    public function __construct(
        Network $network,
        protected readonly ClientboundPacket $packet
    ) {
        parent::__construct($network);
    }

    /** @return ClientboundPacket */
    public function getPacket(): Packet {
        return $this->packet;
    }
}