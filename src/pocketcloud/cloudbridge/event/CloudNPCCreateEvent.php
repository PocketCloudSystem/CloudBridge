<?php

namespace pocketcloud\cloudbridge\event;

use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class CloudNPCCreateEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(private CloudNPC $cloudNPC) {}

    public function getCloudNPC(): CloudNPC {
        return $this->cloudNPC;
    }
}