<?php

namespace pocketcloud\cloud\bridge\util\trait;

trait RegistryTrait {

    protected static ?array $members = null;

    final public static function getAll(): array {
        self::check();
        return self::$members;
    }

    protected static function check(): void {
        if (self::$members === null) {
            self::$members = [];
            static::init();
        }
    }

    protected static function init(): void {}

    final public static function get(string $name): mixed {
        self::check();
        return self::$members[strtoupper($name)] ?? null;
    }

    public static function __callStatic(string $name, array $arguments) {
        self::check();
        if (isset(self::$members[strtoupper($name)])) {
            if (is_callable(self::$members[strtoupper($name)])) {
                return (self::$members[strtoupper($name)])(...$arguments);
            } else {
                return self::$members[strtoupper($name)];
            }
        }
        return null;
    }

    final protected static function register(string $name, mixed $member): void {
        if (self::$members !== null) {
            self::$members[strtoupper($name)] = $member;
        }
    }
}