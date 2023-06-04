<?php

namespace pocketcloud\cloudbridge\util;

use pocketmine\console\ConsoleCommandSender;
use pocketmine\lang\Translatable;
use pocketmine\Server;

class CloudCommandSender extends ConsoleCommandSender {

    public function __construct() {
        parent::__construct(Server::getInstance(), Server::getInstance()->getLanguage());
    }

    private array $cachedMessages = [];

    public function sendMessage(Translatable|string $message): void {
        if ($message instanceof Translatable) $message = $this->getLanguage()->translate($message);
        parent::sendMessage($message);
        $this->cachedMessages[] = $message;
    }

    public function getName(): string {
        return "CLOUD";
    }

    public function getCachedMessages(): array {
        return $this->cachedMessages;
    }
}