<?php

namespace pocketcloud\cloudbridge\api\player;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerTextPacket;
use pocketcloud\cloudbridge\network\packet\impl\types\TextType;
use pocketcloud\cloudbridge\util\Utils;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerKickPacket;
use pocketmine\player\Player;

class CloudPlayer {

    public function __construct(private string $name, private string $host, private string $xboxUserId, private string $uniqueId, private ?CloudServer $currentServer = null, private ?CloudServer $currentProxy = null) {}

    public function getName(): string {
        return $this->name;
    }

    public function getHost(): string {
        return $this->host;
    }

    public function getXboxUserId(): string {
        return $this->xboxUserId;
    }

    public function getUniqueId(): string {
        return $this->uniqueId;
    }

    public function getCurrentServer(): ?CloudServer {
        return $this->currentServer;
    }

    public function getCurrentProxy(): ?CloudServer {
        return $this->currentProxy;
    }

    public function setCurrentServer(?CloudServer $currentServer): void {
        $this->currentServer = $currentServer;
    }

    public function setCurrentProxy(?CloudServer $currentProxy): void {
        $this->currentProxy = $currentProxy;
    }

    public function send(string $message, TextType $textType) {
        Network::getInstance()->sendPacket(new PlayerTextPacket($this->getName(), $message, $textType));
    }

    public function sendMessage(string $message) {
        $this->send($message, TextType::MESSAGE());
    }

    public function sendPopup(string $message) {
        $this->send($message, TextType::POPUP());
    }

    public function sendTip(string $message) {
        $this->send($message, TextType::TIP());
    }

    public function sendTitle(string $message) {
        $this->send($message, TextType::TITLE());
    }

    public function sendActionBarMessage(string $message) {
        $this->send($message, TextType::ACTION_BAR());
    }

    public function sendToastNotification(string $title, string $body) {
        $this->send($title . "\n" .  $body, TextType::TOAST_NOTIFICATION());
    }

    public function kick(string $reason = "") {
        Network::getInstance()->sendPacket(new PlayerKickPacket(
            $this->name, $reason
        ));
    }

    public function toArray(): array {
        return [
            "name" => $this->name,
            "host" => $this->host,
            "xboxUserId" => $this->xboxUserId,
            "uniqueId" => $this->uniqueId,
            "currentServer" => $this->getCurrentServer()?->getName(),
            "currentProxy" => $this->getCurrentProxy()?->getName()
        ];
    }

    public static function fromArray(array $player): ?CloudPlayer {
        if (!Utils::containKeys($player, "name", "host", "xboxUserId", "uniqueId")) return null;
        return new CloudPlayer(
            $player["name"],
            $player["host"],
            $player["xboxUserId"],
            $player["uniqueId"],
            (!isset($player["currentServer"]) ? null : CloudAPI::getInstance()->getServerByName($player["currentServer"])),
            (!isset($player["currentProxy"]) ? null : CloudAPI::getInstance()->getServerByName($player["currentProxy"]))
        );
    }

    public static function fromPlayer(Player $player): CloudPlayer {
        return new CloudPlayer($player->getName(), $player->getNetworkSession()->getIp() . ":" . $player->getNetworkSession()->getPort(), $player->getXuid(), $player->getUniqueId()->toString(), CloudAPI::getInstance()->getCurrentServer());
    }
}