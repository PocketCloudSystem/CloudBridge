<?php

namespace pocketcloud\cloud\bridge\event\network;

use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\Packet;
use pocketcloud\cloud\bridge\util\net\Address;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class NetworkPacketPreSendEvent extends NetworkPacketEvent implements Cancellable {
    use CancellableTrait;

    public function __construct(
        Network $network,
        Address $sender,
        CloudboundPacket $packet
    ) {
        parent::__construct($network, $sender, $packet);
    }

    /** @return CloudboundPacket */
    public function getPacket(): Packet {
        return $this->packet;
    }
}