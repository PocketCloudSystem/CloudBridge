<?php

namespace pocketcloud\cloudbridge\network\packet\impl\types;

use pocketmine\utils\RegistryTrait;

/**
 * @method static LogType INFO()
 * @method static LogType DEBUG()
 * @method static LogType WARN()
 * @method static LogType ERROR()
 */
final class LogType {
    use RegistryTrait;

    protected static function setup(): void {
        self::_registryRegister("info", new LogType("INFO"));
        self::_registryRegister("debug", new LogType("DEBUG"));
        self::_registryRegister("warn", new LogType("WARN"));
        self::_registryRegister("error", new LogType("ERROR"));
    }

    public static function getTypeByName(string $name): ?LogType {
        self::checkInit();
        return self::$members[strtoupper($name)] ?? null;
    }

    /** @return array<LogType> */
    public static function getTypes(): array {
        self::checkInit();
        return self::$members;
    }

    public function __construct(private readonly string $name) {}

    public function getName(): string {
        return $this->name;
    }
}