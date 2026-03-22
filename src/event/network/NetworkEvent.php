<?php

namespace pocketcloud\cloud\bridge\event\network;

use pocketcloud\cloud\bridge\network\Network;
use pocketmine\event\Event;

abstract class NetworkEvent extends Event {

    public function __construct(protected readonly Network $network) {}

    public function getNetwork(): Network {
        return $this->network;
    }
}