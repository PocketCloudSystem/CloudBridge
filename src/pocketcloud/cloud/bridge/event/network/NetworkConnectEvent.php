<?php

namespace pocketcloud\cloud\bridge\event\network;

use pocketcloud\cloud\bridge\util\net\Address;
use pocketmine\event\Event;

final class NetworkConnectEvent extends Event {

    public function __construct(private readonly Address $address) {}

    public function getAddress(): Address {
        return $this->address;
    }
}