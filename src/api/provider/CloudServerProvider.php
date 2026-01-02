<?php

namespace pocketcloud\cloud\bridge\api\provider;

use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use RuntimeException;

final class CloudServerProvider implements CloudAPIProvider {
    use CloudAPIGetProviderTrait;

    /** @var array<CloudServer> */
    private array $servers = [];

    public function add(CloudServer $server): void {
        if ($this->isset($server)) $this->servers[$server->getName()]->sync($server->write());
        else $this->servers[$server->getName()] = $server;
    }

    public function remove(CloudServer $server): void {
        if ($this->isset($server)) unset($this->servers[$server->getName()]);
    }

    public function isset(CloudServer|string $name): bool {
        $name = $name instanceof CloudServer ? $name->getName() : $name;
        return isset($this->servers[$name]);
    }

    public function get(string $name): ?CloudServer {
        return $this->servers[$name] ?? null;
    }

    public function current(): CloudServer {
        return $this->get(CloudEnvironmentConfig::getServerName()) ?? throw new RuntimeException("The return value of current() should not be null, wait for CloudAPI to index");
    }

    public function getAll(): array {
        return $this->servers;
    }
}