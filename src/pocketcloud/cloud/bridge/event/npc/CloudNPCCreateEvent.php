<?php

namespace pocketcloud\cloud\bridge\event\npc;

use pocketcloud\cloud\bridge\module\npc\CloudNPC;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

final class CloudNPCCreateEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(private readonly CloudNPC $cloudNPC) {}

    public function getCloudNPC(): CloudNPC {
        return $this->cloudNPC;
    }
}