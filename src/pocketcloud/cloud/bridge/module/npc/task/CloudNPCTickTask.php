<?php

namespace pocketcloud\cloud\bridge\module\npc\task;

use pocketcloud\cloud\bridge\module\npc\CloudNPC;
use pocketmine\scheduler\Task;

class CloudNPCTickTask extends Task {

    public function __construct(private readonly CloudNPC $cloudNPC) {}

    public function onRun(): void {
        $this->cloudNPC->tick();
    }
}