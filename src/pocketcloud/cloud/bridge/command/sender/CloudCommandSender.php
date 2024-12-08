<?php

namespace pocketcloud\cloud\bridge\command\sender;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\lang\Translatable;
use pocketmine\Server;

final class CloudCommandSender extends ConsoleCommandSender {

    public function __construct() {
        parent::__construct(Server::getInstance(), Server::getInstance()->getLanguage());
    }

    private array $cachedMessages = [];

    public function sendMessage(Translatable|string $message): void {
        if ($message instanceof Translatable) $message = $this->getLanguage()->translate($message);
        $this->cachedMessages[] = $message;
        foreach (explode("\n", trim($message)) as $line) {
            CloudBridge::getInstance()->getLogger()->info($line);
        }
    }

    public function getName(): string {
        return "CLOUD";
    }

    public function getCachedMessages(): array {
        return $this->cachedMessages;
    }
}