<?php

namespace pocketcloud\cloudbridge\api\server\status;

use pocketmine\utils\RegistryTrait;

/**
 * @method static ServerStatus STARTING()
 * @method static ServerStatus ONLINE()
 * @method static ServerStatus FULL()
 * @method static ServerStatus IN_GAME()
 * @method static ServerStatus STOPPING()
 * @method static ServerStatus OFFLINE()
 */

final class ServerStatus {
    use RegistryTrait;

    protected static function setup(): void {
        self::_registryRegister("starting", new ServerStatus("STARTING", "§2STARTING"));
        self::_registryRegister("online", new ServerStatus("ONLINE", "§aONLINE"));
        self::_registryRegister("full", new ServerStatus("FULL", "§eFULL"));
        self::_registryRegister("in_game", new ServerStatus("IN_GAME", "§6INGAME"));
        self::_registryRegister("stopping", new ServerStatus("STOPPING", "§4STOPPING"));
        self::_registryRegister("offline", new ServerStatus("OFFLINE", "§cOFFLINE"));
    }

    public static function getServerStatusByName(string $name): ?ServerStatus {
        self::checkInit();
        return self::$members[strtoupper($name)] ?? null;
    }

    public static function getServerStatuses(): array {
        self::checkInit();
        return self::$members;
    }

    public function __construct(private string $name, private string $display) {}

    public function getName(): string {
        return $this->name;
    }

    public function getDisplay(): string {
        return $this->display;
    }
}