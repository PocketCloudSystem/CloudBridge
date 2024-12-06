<?php

namespace pocketcloud\cloud\bridge\event\npc;

use pocketcloud\cloud\bridge\module\npc\CloudNPC;
use pocketmine\entity\Human;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class CloudNPCSpawnEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(
        private readonly CloudNPC $cloudNPC,
        private readonly Human $human
    ) {}

    public function getCloudNPC(): CloudNPC {
        return $this->cloudNPC;
    }

    public function getEntity(): Human {
        return $this->human;
    }
}