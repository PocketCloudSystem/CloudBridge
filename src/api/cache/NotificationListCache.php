<?php

namespace pocketcloud\cloud\bridge\api\cache;

use pocketcloud\cloud\bridge\network\packet\impl\PlayerUpdateNotificationStatePacket;

final class NotificationListCache {

    private static array $notificationList = [];

    /** @internal  */
    public static function sync(array $notificationList): void {
        self::$notificationList = [];
        foreach ($notificationList as $player) self::$notificationList[$player] = $player;
    }

    public static function add(string $player): void {
        if (self::is($player)) return;
        self::$notificationList[$player] = $player;
        PlayerUpdateNotificationStatePacket::create($player, true)->sendPacket();
    }

    public static function remove(string $player): void {
        if (!self::is($player)) return;
        unset(self::$notificationList[$player]);
        PlayerUpdateNotificationStatePacket::create($player, false)->sendPacket();
    }

    public static function is(string $player): bool {
        return isset(self::$notificationList[$player]);
    }

    public static function getAll(): array {
        return array_values(self::$notificationList);
    }
}