<?php

namespace pocketcloud\cloudbridge\network\packet\impl\types;

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

    public static function getReasonByName(string $name): ?DisconnectReason {
        $reason = self::_registryFromString($name);
        if ($reason instanceof DisconnectReason) return $reason;
        return null;
    }

    public function __construct(private string $name) {}

    public function getName(): string {
        return $this->name;
    }
}