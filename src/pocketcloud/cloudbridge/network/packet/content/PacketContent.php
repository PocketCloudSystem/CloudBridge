<?php

namespace pocketcloud\cloudbridge\network\packet\content;

use pocketcloud\cloudbridge\api\player\CloudPlayer;
use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\api\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\network\packet\impl\types\DisconnectReason;
use pocketcloud\cloudbridge\network\packet\impl\types\ErrorReason;
use pocketcloud\cloudbridge\network\packet\impl\types\TextType;
use pocketcloud\cloudbridge\network\packet\impl\types\VerifyStatus;

class PacketContent {

    public function __construct(private array $content) {}

    public function put($v) {
        $this->content[] = $v;
    }

    public function putServerStatus(ServerStatus $serverStatus) {
        $this->content[] = $serverStatus->getName();
    }

    public function putVerifyStatus(VerifyStatus $status) {
        $this->content[] = $status->getName();
    }

    public function putDisconnectReason(DisconnectReason $disconnectReason) {
        $this->content[] = $disconnectReason->getName();
    }

    public function putTextType(TextType $textType) {
        $this->content[] = $textType->getName();
    }

    public function putServer(CloudServer $server) {
        $this->content[] = $server->toArray();
    }

    public function putTemplate(Template $template) {
        $this->content[] = $template->toArray();
    }

    public function putPlayer(CloudPlayer $player) {
        $this->content[] = $player->toArray();
    }

    public function putErrorReason(ErrorReason $errorReason) {
        $this->content[] = $errorReason->getName();
    }

    public function read(): mixed {
        if (count($this->content) > 0) {
            $get = $this->content[0];
            unset($this->content[0]);
            $this->content = array_values($this->content);
            return $get;
        }
        return null;
    }

    public function readString(): ?string {
        $read = $this->read();
        if ($read === null) return null;
        return (string) $read;
    }

    public function readInt(): ?int {
        $read = $this->read();
        if ($read === null) return null;
        return intval($read);
    }

    public function readFloat(): ?float {
        $read = $this->read();
        if ($read === null) return null;
        return floatval($read);
    }

    public function readBool(): ?bool {
        $read = $this->read();
        if ($read === null) return null;
        return boolval($read);
    }

    public function readArray(): ?array {
        $read = $this->read();
        if ($read === null) return null;
        if (is_array($read)) return $read;
        return [];
    }

    public function readServerStatus(): ?ServerStatus {
        return ServerStatus::getServerStatusByName($this->readString());
    }

    public function readVerifyStatus(): ?VerifyStatus {
        return VerifyStatus::getStatusByName($this->readString());
    }

    public function readDisconnectReason(): ?DisconnectReason {
        return DisconnectReason::getReasonByName($this->readString());
    }

    public function readTextType(): ?TextType {
        return TextType::getTypeByName($this->readString());
    }

    public function readServer(): ?CloudServer {
        return CloudServer::fromArray($this->readArray());
    }

    public function readTemplate(): ?Template {
        return Template::fromArray($this->readArray());
    }

    public function readPlayer(): ?CloudPlayer {
        return CloudPlayer::fromArray($this->readArray());
    }

    public function readErrorReason(): ?ErrorReason {
        return ErrorReason::getReasonByName($this->readString());
    }

    public function getContent(): array {
        return $this->content;
    }
}