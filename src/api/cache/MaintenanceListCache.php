<?php

namespace pocketcloud\cloud\bridge\api\cache;

final class MaintenanceListCache {

    private static array $maintenanceList = [];

    /** @internal  */
    public static function sync(array $maintenanceList): void {
        foreach ($maintenanceList as $player) self::$maintenanceList[$player] = $player;
    }

    public static function add(string $player): void {
        self::$maintenanceList[$player] = $player;
    }

    public static function remove(string $player): void {
        if (self::is($player)) unset(self::$maintenanceList[$player]);
    }

    public static function is(string $player): bool {
        return isset(self::$maintenanceList[$player]);
    }

    public static function getAll(): array {
        return array_values(self::$maintenanceList);
    }
}