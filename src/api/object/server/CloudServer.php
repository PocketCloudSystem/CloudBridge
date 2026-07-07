<?php

namespace pocketcloud\cloud\bridge\api\object\server;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\object\server\data\CloudServerData;
use pocketcloud\cloud\bridge\api\object\server\data\CloudServerStorage;
use pocketcloud\cloud\bridge\api\object\server\util\ServerStatus;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\network\packet\type\VerificationStatus;
use pocketcloud\cloud\bridge\network\packet\impl\ServerChangeStatusPacket;
use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\mapper\MapperUtils;

final class CloudServer implements Writeable {

    private CloudServerStorage $storage;
    private VerificationStatus $verificationStatus = VerificationStatus::PENDING;
    private ?int $startTime = null;
    private ?int $verifiedTime = null;

    public function __construct(
        private readonly int $id,
        private readonly string $uuid,
        private readonly string $templateName,
        private readonly CloudServerData $serverData,
        private ServerStatus $status,
        array $storage = []
    ) {
        $this->storage = new CloudServerStorage($this, $storage);
    }

    public static function read(array $data): ?self {
        return MapperUtils::fromMap($data, self::class);
    }

    /** @internal */
    public function sync(array $data): void {
        $this->status = isset($data["status"]) ? ServerStatus::fromName($data["status"]) : $this->status;
        if (isset($data["internalStorage"])) $this->storage->sync($data["internalStorage"]);
        if (isset($data["startTime"])) $this->startTime = $data["startTime"];
        if (isset($data["verifiedTime"])) $this->verifiedTime = $data["verifiedTime"];
        if (isset($data["verificationStatus"])) $this->verificationStatus = VerificationStatus::fromName($data["verificationStatus"]);
    }

    public function setServerStatus(ServerStatus $status): void {
        $this->status = $status;
        ServerChangeStatusPacket::create($this->uuid, $status)->sendPacket();
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

    public function getName(): string {
        return $this->templateName . "-" . $this->id;
    }

    public function getPlayerCount(): int {
        return count($this->getPlayers());
    }

    public function getId(): int {
        return $this->id;
    }

    public function getTemplate(): Template {
        return TemplateProvider::provider()->get($this->templateName);
    }

    public function getTemplateName(): string {
        return $this->templateName;
    }

    public function getServerData(): CloudServerData {
        return $this->serverData;
    }

    public function getServerStatus(): ServerStatus {
        return $this->status;
    }

    public function getVerificationStatus(): VerificationStatus {
        return $this->verificationStatus;
    }

    public function getStorage(): CloudServerStorage {
        return $this->storage;
    }

    public function getStartTime(): ?int {
        return $this->startTime;
    }

    public function getVerifiedTime(): ?int {
        return $this->verifiedTime;
    }

    public function getUuid(): string {
        return $this->uuid;
    }

    public function write(): array {
        return MapperUtils::toMap($this);
    }
}