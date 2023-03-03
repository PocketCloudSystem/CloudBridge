<?php

namespace pocketcloud\cloudbridge\event;

use pocketcloud\cloudbridge\module\sign\CloudSign;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class CloudSignAddEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(private CloudSign $cloudSign) {}

    public function getCloudSign(): CloudSign {
        return $this->cloudSign;
    }
}