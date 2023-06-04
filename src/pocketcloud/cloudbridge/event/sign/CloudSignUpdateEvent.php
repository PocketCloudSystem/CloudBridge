<?php

namespace pocketcloud\cloudbridge\event\sign;

use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\module\sign\CloudSign;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class CloudSignUpdateEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(private CloudSign $cloudSign, private ?CloudServer $oldUsingServer, private ?CloudServer $newUsingServer) {}

    public function getCloudSign(): CloudSign {
        return $this->cloudSign;
    }

    public function getOldUsingServer(): ?CloudServer {
        return $this->oldUsingServer;
    }

    public function getNewUsingServer(): ?CloudServer {
        return $this->newUsingServer;
    }

    public function setNewUsingServer(?CloudServer $newUsingServer): void {
        $this->newUsingServer = $newUsingServer;
    }
}