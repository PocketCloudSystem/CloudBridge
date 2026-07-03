<?php

namespace pocketcloud\cloud\bridge\api\object\player;

use Exception;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\network\packet\type\TextType;
use pocketcloud\cloud\bridge\network\packet\impl\PlayerKickPacket;
use pocketcloud\cloud\bridge\network\packet\impl\PlayerTextPacket;
use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\Utils;
use pocketcloud\cloud\bridge\util\mapper\MapperUtils;
use pocketmine\player\Player;

final class CloudPlayer implements Writeable {

    public function __construct(
        private readonly string $name,
        private readonly string $address,
        private readonly string $xboxUserId,
        private readonly string $uniqueId,
        private ?string $currentServerName,
        private ?string $currentProxyName
    ) {}

    public static function read(array $data): ?self {
        try {
            return MapperUtils::fromMap($data, self::class);
        } catch (Exception) {
            return null;
        }
    }

    public static function fromPlayer(Player $player): self {
        return new self(
            $player->getName(),
            $player->getNetworkSession()->getIp(),
            $player->getXuid(),
            $player->getUniqueId()->toString(),
            null, null
        );
    }

    public function getUniqueId(): string {
        return $this->uniqueId;
    }

    /** @internal */
    public function sync(array $data): void {
        $this->currentServerName = array_key_exists("currentServerName", $data) ? $data["currentServerName"] : $this->currentServerName;
        $this->currentProxyName = array_key_exists("currentProxyName", $data) ? $data["currentProxyName"] : $this->currentProxyName;
    }

    public function send(string $message, TextType $textType): void {
        PlayerTextPacket::create($this->getName(), $message, $textType)->sendPacket();
    }

    public function sendMessage(string $message): void {
        $this->send($message, TextType::MESSAGE);
    }

    public function getName(): string {
        return $this->name;
    }

    public function sendPopup(string $message): void {
        $this->send($message, TextType::POPUP);
    }

    public function sendTip(string $message): void {
        $this->send($message, TextType::TIP);
    }

    public function sendTitle(string $message): void {
        $this->send($message, TextType::TITLE);
    }

    public function sendActionBarMessage(string $message): void {
        $this->send($message, TextType::ACTION_BAR);
    }

    public function sendToastNotification(string $title, string $body): void {
        $this->send($title . "\n" . $body, TextType::TOAST_NOTIFICATION);
    }

    public function kick(string $reason = "", string $disconnectScreenMessage = ""): void {
        PlayerKickPacket::create($this->name, $reason, $disconnectScreenMessage)->sendPacket();
    }

    public function getAddress(): string {
        return $this->address;
    }

    public function getXboxUserId(): string {
        return $this->xboxUserId;
    }

    public function setCurrentServer(CloudServer|string|null $currentServer): void {
        $currentServer = ($currentServer instanceof CloudServer ? $currentServer->getName() : (is_string($currentServer) ? $currentServer : null));
        $this->currentServerName = $currentServer;
    }

    public function setCurrentProxy(CloudServer|string|null $currentProxy): void {
        $currentProxy = ($currentProxy instanceof CloudServer ? $currentProxy->getName() : (is_string($currentProxy) ? $currentProxy : null));
        $this->currentProxyName = $currentProxy;
    }

    public function getCurrentServer(): ?CloudServer {
        return CloudServerProvider::provider()->get($this->currentServerName);
    }

    public function getCurrentProxy(): ?CloudServer {
        return CloudServerProvider::provider()->get($this->currentProxyName);
    }

    public function getCurrentServerName(): ?string {
        return $this->currentServerName;
    }

    public function getCurrentProxyName(): ?string {
        return $this->currentProxyName;
    }

    public function write(): array {
        return MapperUtils::toMap($this);
    }
}