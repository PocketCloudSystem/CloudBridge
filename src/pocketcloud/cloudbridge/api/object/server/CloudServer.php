<?php

namespace pocketcloud\cloudbridge\api\object\server;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\object\player\CloudPlayer;
use pocketcloud\cloudbridge\api\object\server\data\CloudServerData;
use pocketcloud\cloudbridge\api\object\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\object\template\Template;
use pocketcloud\cloudbridge\util\Utils;

class CloudServer {

    public function __construct(
        private readonly int $id,
        private readonly Template $template,
        private readonly CloudServerData $cloudServerData,
        private ServerStatus $serverStatus
    ) {}

    public function getName(): string {
        return $this->template->getName() . "-" . $this->id;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getTemplate(): Template {
        return $this->template;
    }

    public function getCloudServerData(): CloudServerData {
        return $this->cloudServerData;
    }

    public function getServerStatus(): ServerStatus {
        return $this->serverStatus;
    }

    public function setServerStatus(ServerStatus $serverStatus): void {
        $this->serverStatus = $serverStatus;
    }

    public function getCloudPlayer(string $name): ?CloudPlayer {
        foreach ($this->getCloudPlayers() as $player) if ($player->getName() == $name) return $player;
        return null;
    }

    /** @return array<CloudPlayer> */
    public function getCloudPlayers(): array {
        return array_filter(CloudAPI::playerProvider()->getPlayers(), fn(CloudPlayer $player) => ($this->template->getTemplateType() === "SERVER" ? $player->getCurrentServer() === $this : $player->getCurrentProxy() === $this));
    }

    public function toArray(): array {
        return [
            "name" => $this->getName(),
            "id" => $this->id,
            "template" => $this->template->getName(),
            "port" => $this->getCloudServerData()->getPort(),
            "maxPlayers" => $this->getCloudServerData()->getMaxPlayers(),
            "processId" => $this->getCloudServerData()->getProcessId(),
            "serverStatus" => $this->getServerStatus()->getName()
        ];
    }

    public static function fromArray(array $server): ?CloudServer {
        if (!Utils::containKeys($server, "name", "id", "template", "port", "maxPlayers", "processId", "serverStatus")) return null;
        if (($template = CloudAPI::templateProvider()->getTemplate($server["template"])) === null) return null;
        return new CloudServer(
            intval($server["id"]),
            $template,
            new CloudServerData(intval($server["port"]), intval($server["maxPlayers"]), intval($server["processId"])),
            ServerStatus::get($server["serverStatus"]) ?? ServerStatus::ONLINE()
        );
    }
}