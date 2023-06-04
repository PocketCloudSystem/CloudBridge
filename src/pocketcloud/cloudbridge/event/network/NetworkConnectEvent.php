<?php

namespace pocketcloud\cloudbridge\event\network;

use pocketcloud\cloudbridge\util\Address;
use pocketmine\event\Event;

class NetworkConnectEvent extends Event {

    public function __construct(private Address $address) {}

    public function getAddress(): Address {
        return $this->address;
    }
}