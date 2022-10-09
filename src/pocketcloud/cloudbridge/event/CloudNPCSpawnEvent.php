<?php

namespace pocketcloud\cloudbridge\event;

use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketmine\entity\Human;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class CloudNPCSpawnEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(private CloudNPC $cloudNPC, private Human $human) {}

    public function getCloudNPC(): CloudNPC {
        return $this->cloudNPC;
    }

    public function getEntity(): Human {
        return $this->human;
    }
}