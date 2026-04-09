<?php

namespace pocketcloud\cloud\bridge\api\provider;

use pocketcloud\cloud\bridge\api\object\group\ServerGroup;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\command\util\ParameterType;
use pocketcloud\cloud\bridge\network\packet\impl\request\ServerSaveRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\ServerStartRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\ServerStopRequestPacket;
use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use RuntimeException;

final class CloudServerProvider implements CloudAPIProvider {
    use CloudAPIGetProviderTrait;

    /** @var array<CloudServer> */
    private array $servers = [];

    public function start(Template|string $template, int $count = 1): RequestPacket {
        $template = $template instanceof Template ? $template->getName() : $template;
        if ($count < 0) $count = 1;
        return ServerStartRequestPacket::create($template, $count)->sendRequest();
    }

    public function stop(Template|CloudServer|ServerGroup|string $server, bool $forcefully = false): RequestPacket {
        $server = is_string($server) ? $server : $server->getName();
        return ServerStopRequestPacket::create($server, $forcefully)->sendRequest();
    }

    public function save(CloudServer|string $server): RequestPacket {
        $server = $server instanceof CloudServer ? $server->getName() : $server;
        return ServerSaveRequestPacket::create($server)->sendRequest();
    }

    public function freeServer(Template $template, array $exclusions = [], bool $prioritizeLowServers = false): ?CloudServer {
        $availableServers = array_filter($this->getAll($template), fn(CloudServer $server) => !in_array($server->getName(), $exclusions) &&
            $server->getServerStatus()->isOnline(true));
        if (empty($availableServers)) return null;
        $serverClasses = array_map(fn(CloudServer $server) => $server, $availableServers);
        $servers = array_map(fn(CloudServer $server) => count($server->getPlayers()), $availableServers);
        arsort($servers);
        return ($prioritizeLowServers ? ($serverClasses[array_key_last($servers)] ?? null) : ($serverClasses[array_key_first($servers)] ?? null));
    }

    public function getAll(?Template $template = null): array {
        if ($template !== null) return array_filter($this->servers, fn(CloudServer $server) => $template->getName() == $server->getTemplate()->getName());
        return $this->servers;
    }

    public function add(CloudServer $server): void {
        if ($this->isset($server)) {
            $this->servers[strtolower($server->getName())]->sync($server->write());
        } else {
            $this->servers[strtolower($server->getName())] = $server;
            ParameterType::updateEnum(ParameterType::SERVER);
        }
    }

    public function isset(CloudServer|string $name): bool {
        $name = $name instanceof CloudServer ? $name->getName() : $name;
        return isset($this->servers[strtolower($name)]);
    }

    public function remove(CloudServer $server): void {
        if ($this->isset($server)) {
            unset($this->servers[strtolower($server->getName())]);
            ParameterType::updateEnum(ParameterType::SERVER);
        }
    }

    public function current(): CloudServer {
        return $this->get(CloudEnvironmentConfig::getServerName()) ?? throw new RuntimeException("The return value of current() should not be null, wait for CloudAPI to index");
    }

    public function get(string $name): ?CloudServer {
        return $this->servers[strtolower($name)] ?? null;
    }
}