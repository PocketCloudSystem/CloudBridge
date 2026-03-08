<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\task;

use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketmine\scheduler\Task;

final class CloudNPCTickTask extends Task {

    public function __construct() {}

    public function onRun(): void {
        foreach (CloudNPCModule::get()->getAll() as $npc) $npc->tick();
    }
}