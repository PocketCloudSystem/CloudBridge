<?php

namespace pocketcloud\cloud\bridge\event\sign;

use pocketcloud\cloud\bridge\module\sign\CloudSign;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

final class CloudSignAddEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(private readonly CloudSign $cloudSign) {}

    public function getCloudSign(): CloudSign {
        return $this->cloudSign;
    }
}