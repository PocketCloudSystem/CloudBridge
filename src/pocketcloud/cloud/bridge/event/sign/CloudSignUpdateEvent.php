<?php

namespace pocketcloud\cloud\bridge\event\sign;

use pocketcloud\cloud\bridge\module\sign\CloudSign;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

final class CloudSignUpdateEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(
        private readonly CloudSign $cloudSign,
        private readonly ?string $oldUsingServer,
        private ?string $newUsingServer
    ) {}

    public function getCloudSign(): CloudSign {
        return $this->cloudSign;
    }

    public function getOldUsingServer(): ?string {
        return $this->oldUsingServer;
    }

    public function getNewUsingServer(): ?string {
        return $this->newUsingServer;
    }

    public function setNewUsingServer(?string $newUsingServer): void {
        $this->newUsingServer = $newUsingServer;
    }
}