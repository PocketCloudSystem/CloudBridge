<?php

namespace pocketcloud\cloud\bridge\command\sender;

use pocketmine\console\ConsoleCommandSender;
use pocketmine\lang\Language;
use pocketmine\lang\Translatable;
use pocketmine\Server;

final class CloudCommandSender extends ConsoleCommandSender {

    private array $cachedMessages = [];

    public function __construct(private readonly string $id, Server $server, Language $language) {
        parent::__construct($server, $language);
    }

    public function sendMessage(Translatable|string $message): void {
        parent::sendMessage($message);
        if ($message instanceof Translatable) $message = $this->getLanguage()->translate($message);
        $this->cachedMessages[] = $message;
    }

    public function getId(): string {
        return $this->id;
    }

    public function getName(): string {
        return "Cloud-" . $this->id;
    }

    public function getCachedMessages(): array {
        return $this->cachedMessages;
    }
}