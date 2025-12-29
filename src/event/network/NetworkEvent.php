<?php

namespace pocketcloud\cloud\bridge\event\network;

use pocketcloud\cloud\bridge\util\net\Address;
use pocketmine\event\Event;

abstract class NetworkEvent extends Event {

    public function __construct(private readonly Address $sender) {}

    public function getSender(): Address {
        return $this->sender;
    }
}