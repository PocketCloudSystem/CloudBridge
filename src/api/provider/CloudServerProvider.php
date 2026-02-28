<?php

namespace pocketcloud\cloud\bridge\api\provider;

use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use RuntimeException;

final class CloudServerProvider implements CloudAPIProvider {
    use CloudAPIGetProviderTrait;

    /** @var array<CloudServer> */
    private array $servers = [];

    //TODO: add startServer, stopServer, saveServer, etc.

    public function freeServer(Template $template, array $exclusions = [], bool $prioritizeLowServers = false): ?CloudServer {
        $availableServers = array_filter($this->getAll($template), fn(CloudServer $server) => !in_array($server->getName(), $exclusions) && $server->getServerStatus()->isOnline(true));
        if (empty($availableServers)) return null;
        $serverClasses = array_map(fn(CloudServer $server) => $server, $availableServers);
        $servers = array_map(fn(CloudServer $server) => count($server->getPlayers()), $availableServers);
        arsort($servers);
        return ($prioritizeLowServers ? ($serverClasses[array_key_last($servers)] ?? null) : ($serverClasses[array_key_first($servers)] ?? null));
    }

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

    public function getAll(?Template $template = null): array {
        if ($template !== null) return array_filter($this->servers, fn(CloudServer $server) => $template->getName() == $server->getTemplate()->getName());
        return $this->servers;
    }
}