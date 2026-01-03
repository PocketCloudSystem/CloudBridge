<?php

namespace pocketcloud\cloud\bridge\language;

use pocketcloud\cloud\bridge\CloudBridge;
use Throwable;

final class Language {

    private static ?self $current = null;

    public static function sync(string $currentLanguage, array $messages): void {
        self::$current = new self($currentLanguage, $messages);
    }

    public static function current(): Language {
        if (self::$current === null) return new self("Unknown", []);
        return self::$current;
    }

    public function __construct(
        private readonly string $name,
        private readonly array $messages = []
    ) {}

    public function translate(string $key, array $args = []): string {
        $message = str_replace("{PREFIX}", $this->messages["inGame.prefix"] ?? "", $this->messages[$key] ?? $key);
        foreach ($args as $i => $arg) {
            try {
                $message = str_replace("%" . $i . "%", $arg, $message);
            } catch (Throwable $exception) {
                CloudBridge::getInstance()->getLogger()->logException($exception);
            }
        }
        return $message;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getMessages(): array {
        return $this->messages;
    }
}