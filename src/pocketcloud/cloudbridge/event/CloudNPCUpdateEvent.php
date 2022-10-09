<?php

namespace pocketcloud\cloudbridge\event;

use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class CloudNPCUpdateEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(private CloudNPC $cloudNPC, private string $oldNameTag, private string $newNameTag) {}

    public function getCloudNPC(): CloudNPC {
        return $this->cloudNPC;
    }

    public function getOldNameTag(): string {
        return $this->oldNameTag;
    }

    public function getNewNameTag(): string {
        return $this->newNameTag;
    }

    public function setNewNameTag(string $newNameTag): void {
        $this->newNameTag = $newNameTag;
    }
}