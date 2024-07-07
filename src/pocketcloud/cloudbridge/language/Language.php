<?php

namespace pocketcloud\cloudbridge\language;

use pocketcloud\cloudbridge\util\GeneralSettings;
use pocketmine\utils\RegistryTrait;

/**
 * @method static Language GERMAN()
 * @method static Language ENGLISH()
 */
final class Language {
    use RegistryTrait;

    public const FALLBACK = "en";

    protected static function setup(): void {
        self::_registryRegister("german", new Language(
            "German",
            GeneralSettings::getCloudPath() . "storage/de_DE.yml",
            ["de_DE", "ger", "Deutsch"]
        ));

        self::_registryRegister("english", new Language(
            "English",
            GeneralSettings::getCloudPath() . "storage/en_US.yml",
            ["en_US", "en", "Englisch"]
        ));
    }

    public static function init(): void {
        self::checkInit();
    }

    public static function current(): Language {
        return self::getLanguage(GeneralSettings::getLanguage() ?? self::FALLBACK);
    }

    public static function fallback(): Language {
        return self::getLanguage(self::FALLBACK);
    }

    public static function getLanguage(string $name): ?Language {
        /** @var Language $language */
        foreach (self::_registryGetAll() as $language) {
            if ($language->getName() == $name || in_array($name, $language->getAliases())) return $language;
        }
        return null;
    }

    /** @var array<string, string> */
    private array $messages;

    public function __construct(
        private readonly string $name,
        private readonly string $filePath,
        private readonly array $aliases
    ) {
        $this->messages = @yaml_parse(@file_get_contents($this->filePath));
    }

    public function translate(string $key, mixed ...$params): string {
        $message = str_replace("{PREFIX}", $this->messages["inGame.prefix"] ?? "", $this->messages[$key] ?? $key);
        foreach ($params as $i => $param) {
            try {
                $message = str_replace("%" . $i . "%", $param, $message);
            } catch (\Error) {}
        }
        return $message;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getAliases(): array {
        return $this->aliases;
    }

    public function getMessages(): array {
        return $this->messages;
    }
}