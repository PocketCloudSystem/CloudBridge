<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\api\cache\NotificationListCache;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\NotificationType;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;
use pocketmine\player\Player;
use pocketmine\Server;

final class CloudNotificationPacket extends CloudPacket implements ClientboundPacket, CloudboundPacket {

    public function __construct(
        private ?NotificationType $notificationType = null,
        private array $args = []
    ) {}

    public function handle(): void {
        $message = $this->notificationType->getLangKey()->translate($this->args);
        foreach (array_filter(Server::getInstance()->getOnlinePlayers(), fn(Player $player) => NotificationListCache::is($player->getName())) as $player) {
            $player->sendMessage($message);
        }
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->notificationType, $this->args);
    }

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->notificationType, &$this->args], [fn() => $packetData->readNotificationType(), fn() => $packetData->readArray()]);
    }

    public function getNotificationType(): ?NotificationType {
        return $this->notificationType;
    }

    public function getArgs(): array {
        return $this->args;
    }

    public static function create(NotificationType $notificationType, array $args): self {
        return new self($notificationType, $args);
    }
}