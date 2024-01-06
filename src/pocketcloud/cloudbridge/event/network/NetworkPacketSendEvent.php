<?php

namespace pocketcloud\cloudbridge\event\network;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketmine\event\Event;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class NetworkPacketSendEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(private readonly CloudPacket $packet) {}

    public function getPacket(): CloudPacket {
        return $this->packet;
    }
}