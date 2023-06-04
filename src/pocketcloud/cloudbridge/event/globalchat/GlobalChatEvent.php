<?php

namespace pocketcloud\cloudbridge\event\globalchat;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\player\Player;

class GlobalChatEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(
        private Player $player,
        private string $message,
        private string $format)
    {}

    public function getPlayer(): Player {
        return $this->player;
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function getFormat(): string {
        return $this->format;
    }

    public function setFormat(string $format): void {
        $this->format = $format;
    }
}