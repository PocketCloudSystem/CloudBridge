<?php

namespace pocketcloud\cloudbridge\event\sign;

use pocketcloud\cloudbridge\module\sign\CloudSign;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class CloudSignRemoveEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(private readonly CloudSign $cloudSign) {}

    public function getCloudSign(): CloudSign {
        return $this->cloudSign;
    }
}