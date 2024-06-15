<?php

namespace pocketcloud\cloudbridge\api\provider;

use pocketcloud\cloudbridge\api\object\server\CloudServer;
use pocketcloud\cloudbridge\api\object\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\object\template\Template;
use pocketcloud\cloudbridge\api\registry\Registry;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\impl\normal\CloudServerSavePacket;
use pocketcloud\cloudbridge\network\packet\impl\request\CloudServerStartRequestPacket;
use pocketcloud\cloudbridge\network\packet\impl\request\CloudServerStopRequestPacket;
use pocketcloud\cloudbridge\network\packet\RequestPacket;
use pocketcloud\cloudbridge\network\request\RequestManager;
use pocketcloud\cloudbridge\util\GeneralSettings;
use RuntimeException;

class ServerProvider {

    public function current(): CloudServer {
        return $this->getServer(GeneralSettings::getServerName()) ?? throw new RuntimeException("Current server shouldn't be null");
    }

    public function startServer(Template|string $template, int $count = 1): RequestPacket {
        $template = $template instanceof Template ? $template->getName() : $template;
        return RequestManager::getInstance()->sendRequest(new CloudServerStartRequestPacket($template, $count));
    }

    public function stopServer(CloudServer|string $server): RequestPacket {
        $server = $server instanceof CloudServer ? $server->getName() : $server;
        return RequestManager::getInstance()->sendRequest(new CloudServerStopRequestPacket($server));
    }

    public function stopTemplate(Template|string $template): RequestPacket {
        $template = $template instanceof Template ? $template->getName() : $template;
        return RequestManager::getInstance()->sendRequest(new CloudServerStopRequestPacket($template));
    }

    public function saveCurrent(): void {
        Network::getInstance()->sendPacket(new CloudServerSavePacket());
    }

    public function getFreeServerByTemplate(Template $template, array $exclude = [], bool $lowest = false): ?CloudServer {
        $availableServers = array_filter($this->getServersByTemplate($template), fn(CloudServer $server) => !in_array($server->getName(), $exclude) && $server->getServerStatus() === ServerStatus::ONLINE());
        if (empty($availableServers)) return null;
        $serverClasses = array_map(fn(CloudServer $server) => $server, $availableServers);
        $servers = array_map(fn(CloudServer $server) => count($server->getCloudPlayers()), $availableServers);
        arsort($servers);
        return ($lowest ? ($serverClasses[array_key_last($servers)] ?? null) : ($serverClasses[array_key_first($servers)] ?? null));
    }

    /** @return array<CloudServer> */
    public function getServersByTemplate(Template $template): array {
        return array_filter($this->getServers(), function(CloudServer $server) use($template): bool {
            return $template->getName() == $server->getTemplate()->getName();
        });
    }

    public function getServer(string $name): ?CloudServer {
        return $this->getServers()[$name] ?? null;
    }

    /** @return array<CloudServer> */
    public function getServers(): array {
        return Registry::getServers();
    }
}