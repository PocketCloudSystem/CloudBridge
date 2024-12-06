<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\type;

use pocketmine\utils\RegistryTrait;

/**
 * @method static DisconnectReason CLOUD_SHUTDOWN()
 * @method static DisconnectReason SERVER_SHUTDOWN()
 */
final class DisconnectReason {
    use RegistryTrait;

    protected static function setup(): void {
        self::_registryRegister("cloud_shutdown", new DisconnectReason("CLOUD_SHUTDOWN"));
        self::_registryRegister("server_shutdown", new DisconnectReason("SERVER_SHUTDOWN"));
    }

    public static function get(string $name): ?DisconnectReason {
        self::checkInit();
        return self::$members[strtoupper($name)] ?? null;
    }

    public function __construct(private readonly string $name) {}

    public function getName(): string {
        return $this->name;
    }
}