<?php

namespace pocketcloud\cloud\bridge\api\object\server;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\object\server\util\ServerStatus;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\api\object\server\data\CloudServerData;
use pocketcloud\cloud\bridge\api\object\server\data\CloudServerStorage;
use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\Utils;

final class CloudServer implements Writeable {

    private CloudServerStorage $serverStorage;

    public function __construct(
        private readonly int $id,
        private readonly string $serverUuid,
        private readonly string $template,
        private readonly CloudServerData $serverData,
        private ServerStatus $serverStatus,
        array $serverStorage = []
    ) {
        $this->serverStorage = new CloudServerStorage($this, $serverStorage);
    }

    /** @internal */
    public function sync(array $data): void {
        $this->serverStatus = isset($data["serverStatus"]) ? ServerStatus::fromName($data["serverStatus"]) : $this->serverStatus;
        if (isset($data["internalStorage"])) $this->serverStorage->sync($data["internalStorage"]);
    }

    public function setServerStatus(ServerStatus $serverStatus): void {
        $this->serverStatus = $serverStatus;
    }

    public function getPlayer(string $name): ?CloudPlayer {
        return array_find($this->getPlayers(), fn(CloudPlayer $player) => $player->getName() == $name ||
            $player->getUniqueId() == $name ||
            $player->getXboxUserId() == $name
        );
    }

    /** @return array<CloudPlayer> */
    public function getPlayers(): array {
        return array_filter(CloudPlayerProvider::provider()->getAll(), fn(CloudPlayer $player) => $player->getCurrentProxyName() == $this->getName() || $player->getCurrentServerName() == $this->getName());
    }

    public function getCloudPlayerCount(): int {
        return count($this->getPlayers());
    }

    public function getServerUuid(): string {
        return $this->serverUuid;
    }

    public function getName(): string {
        return $this->template . "-" . $this->id;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getTemplate(): Template {
        return TemplateProvider::provider()->get($this->template);
    }

    public function getTemplateName(): string {
        return $this->template;
    }

    public function getServerData(): CloudServerData {
        return $this->serverData;
    }

    public function getServerStatus(): ServerStatus {
        return $this->serverStatus;
    }

    public function getServerStorage(): CloudServerStorage {
        return $this->serverStorage;
    }

    public function write(): array {
        return [
            "name" => $this->getName(),
            "uuid" => $this->getServerUuid(),
            "id" => $this->id,
            "template" => $this->template,
            "port" => $this->serverData->getPort(),
            "maxPlayers" => $this->serverData->getMaxPlayers(),
            "processId" => $this->serverData->getProcessId(),
            "serverStatus" => $this->serverStatus->getName(),
            "internalStorage" => $this->serverStorage->getAll()
        ];
    }

    public static function read(array $data): ?self {
        if (!Utils::containKeys($data, "name", "uuid", "id", "template", "port", "maxPlayers", "serverStatus")) return null;
        return new CloudServer(
            intval($data["id"]),
            $data["uuid"],
            $data["template"],
            new CloudServerData($data["name"], intval($data["port"]), intval($data["maxPlayers"]), intval($data["processId"] ?? null)),
            ServerStatus::fromName($data["serverStatus"]),
            $data["internalStorage"]
        );
    }
}