<?php

namespace pocketcloud\cloudbridge\network\packet\impl\types;

use pocketmine\utils\RegistryTrait;

/**
 * @method static ErrorReason NO_ERROR()
 * @method static ErrorReason TEMPLATE_EXISTENCE()
 * @method static ErrorReason MAX_SERVERS()
 * @method static ErrorReason SERVER_EXISTENCE()
 */
final class ErrorReason {
    use RegistryTrait;

    protected static function setup(): void {
        self::_registryRegister("no_error", new ErrorReason("NO_ERROR"));
        self::_registryRegister("template_existence", new ErrorReason("TEMPLATE_EXISTENCE"));
        self::_registryRegister("max_servers", new ErrorReason("MAX_SERVERS"));
        self::_registryRegister("server_existence", new ErrorReason("SERVER_EXISTENCE"));
    }

    public static function getReasonByName(string $name): ?ErrorReason {
        $reason = self::_registryFromString($name);
        if ($reason instanceof ErrorReason) return $reason;
        return null;
    }

    public function __construct(private string $name) {}

    public function getName(): string {
        return $this->name;
    }
}