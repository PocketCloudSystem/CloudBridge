<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\impl\types\TextType;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\ToastRequestPacket;
use pocketmine\Server;

class PlayerTextPacket extends CloudPacket {

    public function __construct(
        private string $player = "",
        private string $message = "",
        private ?TextType $textType = null
    ) {
        if ($this->textType === null) $this->textType = TextType::MESSAGE();
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->player);
        $packetData->write($this->message);
        $packetData->writeTextType($this->textType);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->player = $packetData->readString();
        $this->message = $packetData->readString();
        $this->textType = $packetData->readTextType();
    }

    public function getPlayer(): string {
        return $this->player;
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function getTextType(): TextType {
        return $this->textType;
    }

    public function handle() {
        $title = "";
        $body = "";
        if ($this->textType === TextType::TOAST_NOTIFICATION()) {
            $explode = explode("\n", $this->message);
            $title = array_shift($explode);
            $body = implode("\n", $explode);
        }

        if ($this->player == "*") {
            if ($this->textType === TextType::MESSAGE()) Server::getInstance()->broadcastMessage($this->message);
            else if ($this->textType === TextType::POPUP()) Server::getInstance()->broadcastPopup($this->message);
            else if ($this->textType === TextType::TIP()) Server::getInstance()->broadcastTip($this->message);
            else if ($this->textType === TextType::TITLE()) Server::getInstance()->broadcastTitle($this->message);
            else if ($this->textType === TextType::ACTION_BAR()) NetworkBroadcastUtils::broadcastPackets(Server::getInstance()->getOnlinePlayers(), [SetTitlePacket::actionBarMessage($this->message)]);
            else if ($this->textType === TextType::TOAST_NOTIFICATION()) NetworkBroadcastUtils::broadcastPackets(Server::getInstance()->getOnlinePlayers(), [ToastRequestPacket::create($title, $body)]);
        } else if (($player = Server::getInstance()->getPlayerExact($this->getPlayer())) !== null) {
            if ($this->textType === TextType::MESSAGE()) $player->sendMessage($this->message);
            else if ($this->textType === TextType::POPUP()) $player->sendPopup($this->message);
            else if ($this->textType === TextType::TIP()) $player->sendTip($this->message);
            else if ($this->textType === TextType::TITLE()) $player->sendTitle($this->message);
            else if ($this->textType === TextType::ACTION_BAR()) $player->sendActionBarMessage($this->message);
            else if ($this->textType === TextType::TOAST_NOTIFICATION()) $player->sendToastNotification($title, $body);
        }
    }
}