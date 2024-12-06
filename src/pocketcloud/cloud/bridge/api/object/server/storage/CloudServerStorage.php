<?php

namespace pocketcloud\cloud\bridge\api\object\server\storage;

use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\impl\normal\CloudServerSyncStoragePacket;
use pocketcloud\cloud\bridge\util\GeneralSettings;

final class CloudServerStorage {

    public function __construct(
        private readonly CloudServer $server,
        private array $storage = []
    ) {}

    /** @internal  */
    public function sync(array $data): void {
        $this->storage = $data;
    }

    private function sendSyncPacket(): void {
        if ($this->server->getName() == GeneralSettings::getServerName()) Network::getInstance()->sendPacket(new CloudServerSyncStoragePacket($this->storage));
    }

    public function put(string $k, mixed $v): self {
        if (!isset($this->storage[$k])) {
            $this->storage[$k] = $v;
            $this->sendSyncPacket();
        }
        return $this;
    }

    public function remove(string $k): self {
        if (isset($this->storage[$k])) {
            unset($this->storage[$k]);
            $this->sendSyncPacket();
        }
        return $this;
    }

    public function has(string $k): bool {
        return isset($this->storage[$k]);
    }

    public function get(string $k, mixed $default = null): mixed {
        return $this->storage[$k] ?? $default;
    }

    public function replace(string $k, mixed $v): self {
        if (isset($this->storage[$k])) {
            $this->storage[$k] = $v;
            $this->sendSyncPacket();
        }
        return $this;
    }

    public function clear(): self {
        $this->storage = [];
        $this->sendSyncPacket();
        return $this;
    }

    public function getServer(): CloudServer {
        return $this->server;
    }

    public function getStorage(): array {
        return $this->storage;
    }
}