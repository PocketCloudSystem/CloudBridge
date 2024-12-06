<?php

namespace pocketcloud\cloud\bridge\api\provider;

use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\server\status\ServerStatus;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\registry\Registry;
use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\impl\normal\CloudServerSavePacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\CloudServerStartRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\CloudServerStopRequestPacket;
use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\network\request\RequestManager;
use pocketcloud\cloud\bridge\util\GeneralSettings;
use RuntimeException;

class ServerProvider {

    public function current(): CloudServer {
        return $this->get(GeneralSettings::getServerName()) ?? throw new RuntimeException("Current server shouldn't be null");
    }

    public function start(Template|string $template, int $count = 1): RequestPacket {
        $template = $template instanceof Template ? $template->getName() : $template;
        return RequestManager::getInstance()->sendRequest(new CloudServerStartRequestPacket($template, $count));
    }

    public function stop(CloudServer|Template|string $object): RequestPacket {
        $object = is_string($object) ? (
            $object
        ) : $object->getName();

        return RequestManager::getInstance()->sendRequest(new CloudServerStopRequestPacket($object));
    }

    public function save(): void {
        Network::getInstance()->sendPacket(new CloudServerSavePacket());
    }

    public function getFreeServer(Template $template, array $exclude = [], bool $lowest = false): ?CloudServer {
        $availableServers = array_filter($this->getAll($template), fn(CloudServer $server) => !in_array($server->getName(), $exclude) && $server->getServerStatus() === ServerStatus::ONLINE());
        if (empty($availableServers)) return null;
        $serverClasses = array_map(fn(CloudServer $server) => $server, $availableServers);
        $servers = array_map(fn(CloudServer $server) => count($server->getCloudPlayers()), $availableServers);
        arsort($servers);
        return ($lowest ? ($serverClasses[array_key_last($servers)] ?? null) : ($serverClasses[array_key_first($servers)] ?? null));
    }

    public function get(string $name): ?CloudServer {
        return $this->getAll()[$name] ?? null;
    }

    /** @return array<CloudServer> */
    public function getAll(?Template $template = null): array {
        if ($template !== null) return array_filter($this->getAll(), function(CloudServer $server) use($template): bool {
            return $template->getName() == $server->getTemplate()->getName();
        });

        return Registry::getServers();
    }
}