<?php

namespace pocketcloud\cloud\bridge\api\cache;

final class NotificationListCache {

    private static array $notificationList = [];

    /** @internal  */
    public static function sync(array $notificationList): void {
        foreach ($notificationList as $player) self::$notificationList[$player] = true;
    }

    public static function add(string $player): void {
        self::$notificationList[$player] = true;
    }

    public static function remove(string $player): void {
        if (self::is($player)) unset(self::$notificationList[$player]);
    }

    public static function is(string $player): bool {
        return self::$notificationList[$player] ?? false;
    }

    public static function getAll(): array {
        return array_keys(self::$notificationList);
    }
}