<?php

namespace pocketcloud\cloudbridge\event\npc;

use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class CloudNPCRemoveEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(private readonly CloudNPC $cloudNPC) {}

    public function getCloudNPC(): CloudNPC {
        return $this->cloudNPC;
    }
}