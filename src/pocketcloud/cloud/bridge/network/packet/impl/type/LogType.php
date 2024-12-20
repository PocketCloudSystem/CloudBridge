<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\type;

use pocketmine\utils\RegistryTrait;

/**
 * @method static LogType INFO()
 * @method static LogType DEBUG()
 * @method static LogType WARN()
 * @method static LogType ERROR()
 * @method static LogType SUCCESS()
 */
final class LogType {
    use RegistryTrait;

    protected static function setup(): void {
        self::_registryRegister("info", new LogType("INFO"));
        self::_registryRegister("debug", new LogType("DEBUG"));
        self::_registryRegister("warn", new LogType("WARN"));
        self::_registryRegister("error", new LogType("ERROR"));
        self::_registryRegister("success", new LogType("SUCCESS"));
    }

    public static function get(string $name): ?LogType {
        self::checkInit();
        return self::$members[strtoupper($name)] ?? null;
    }

    /** @return array<LogType> */
    public static function getAll(): array {
        self::checkInit();
        return self::$members;
    }

    public function __construct(private readonly string $name) {}

    public function getName(): string {
        return $this->name;
    }
}