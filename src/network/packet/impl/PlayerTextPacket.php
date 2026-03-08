<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\TextType;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;
use pocketmine\Server;

final class PlayerTextPacket extends CloudPacket implements CloudboundPacket, ClientboundPacket {

    public function __construct(
        private string $player = "",
        private string $text = "",
        private ?TextType $type = null
    ) {}

    public static function create(string $player, string $text, TextType $type): self {
        return new self($player, $text, $type);
    }

    public function handle(): void {
        if (($player = Server::getInstance()->getPlayerExact($this->player)) !== null) {
            switch ($this->type) {
                case TextType::MESSAGE:
                    $player->sendMessage($this->text);
                    break;
                case TextType::POPUP:
                    $player->sendPopup($this->text);
                    break;
                case TextType::TIP:
                    $player->sendTip($this->text);
                    break;
                case TextType::TITLE:
                    $parts = explode("\n", $this->text);
                    $title = array_shift($parts);
                    $subTitle = implode("\n", $parts);
                    $player->sendTitle($title, $subTitle);
                    break;
                case TextType::ACTION_BAR:
                    $player->sendActionBarMessage($this->text);
                    break;
                case TextType::TOAST_NOTIFICATION:
                    $title = explode("\n", $this->text)[0];
                    $body = explode("\n", $this->text)[1] ?? "";
                    $player->sendToastNotification($title, $body);
            }
        }
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->player, $this->text, $this->type);
    }

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->player, &$this->text, &$this->type], [fn() => $packetData->readString(), fn() => $packetData->readString(), fn() => $packetData->readTextType()]);
    }

    public function getPlayer(): string {
        return $this->player;
    }

    public function getText(): string {
        return $this->text;
    }

    public function getType(): ?TextType {
        return $this->type;
    }
}