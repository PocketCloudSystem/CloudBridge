<?php

namespace pocketcloud\cloudbridge\util;

class ModuleSettings {

    private static array $data = [
        "sign" => ["enabled" => true],
        "npc" => ["enabled" => true],
        "hub_command" => ["enabled" => true],
    ];

    public static function sync(array $data): void {
        self::$data["sign"]["enabled"] = $data["sign"]["enabled"];
        self::$data["npc"]["enabled"] = $data["npc"]["enabled"];
        self::$data["hub_command"]["enabled"] = $data["hub_command"]["enabled"];
    }

    public static function isSignModuleEnabled(): bool {
        return self::$data["sign"]["enabled"];
    }

    public static function isNpcModuleEnabled(): bool {
        return self::$data["npc"]["enabled"];
    }

    public static function isHubCommandModuleEnabled(): bool {
        return self::$data["hub_command"]["enabled"];
    }
}