<?php

namespace pocketcloud\cloudbridge\event;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketmine\event\Event;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class NetworkPacketReceiveEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(private CloudPacket $packet) {}

    public function getPacket(): CloudPacket {
        return $this->packet;
    }
}