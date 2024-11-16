<?php

namespace pocketcloud\cloudbridge\api\object\server\storage;

use pocketcloud\cloudbridge\api\object\server\CloudServer;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\impl\normal\CloudServerSyncStoragePacket;

final class CloudServerStorage {

    public function __construct(
        private readonly CloudServer $server,
        private array $storage = []
    ) {}

    /** @internal  */
    public function sync(array $data): void {
        $this->storage = $data;
    }

    public function put(string $k, mixed $v): self {
        if (!isset($this->storage[$k])) {
            $this->storage[$k] = $v;
            Network::getInstance()->sendPacket(new CloudServerSyncStoragePacket($this->storage));
        }
        return $this;
    }

    public function remove(string $k): self {
        if (isset($this->storage[$k])) {
            unset($this->storage[$k]);
            Network::getInstance()->sendPacket(new CloudServerSyncStoragePacket($this->storage));
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
            Network::getInstance()->sendPacket(new CloudServerSyncStoragePacket($this->storage));
        }
        return $this;
    }

    public function clear(): self {
        $this->storage = [];
        Network::getInstance()->sendPacket(new CloudServerSyncStoragePacket($this->storage));
        return $this;
    }

    public function getServer(): CloudServer {
        return $this->server;
    }

    public function getStorage(): array {
        return $this->storage;
    }
}