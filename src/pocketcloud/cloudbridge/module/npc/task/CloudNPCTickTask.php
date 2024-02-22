<?php

namespace pocketcloud\cloudbridge\module\npc\task;

use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketmine\scheduler\Task;

class CloudNPCTickTask extends Task {

    public function __construct(private readonly CloudNPC $cloudNPC) {}

    public function onRun(): void {
        $this->cloudNPC->tick();
    }
}