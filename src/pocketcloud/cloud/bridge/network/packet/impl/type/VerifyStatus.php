<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\type;

use pocketmine\utils\RegistryTrait;

/**
 * @method static VerifyStatus DENIED()
 * @method static VerifyStatus VERIFIED()
 * @method static VerifyStatus NOT_APPLIED()
 */
final class VerifyStatus {
    use RegistryTrait;

    protected static function setup(): void {
        self::_registryRegister("DENIED", new VerifyStatus("DENIED"));
        self::_registryRegister("VERIFIED", new VerifyStatus("VERIFIED"));
        self::_registryRegister("NOT_APPLIED", new VerifyStatus("NOT_APPLIED"));
    }

    public static function get(string $name): ?VerifyStatus {
        self::checkInit();
        return self::$members[strtoupper($name)] ?? null;
    }

    public function __construct(private readonly string $name) {}

    public function getName(): string {
        return $this->name;
    }
}