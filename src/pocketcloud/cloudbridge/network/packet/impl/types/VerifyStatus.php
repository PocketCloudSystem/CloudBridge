<?php

namespace pocketcloud\cloudbridge\network\packet\impl\types;

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

    public static function getStatusByName(string $name): ?VerifyStatus {
        self::checkInit();
        return self::$members[strtoupper($name)] ?? null;
    }

    public function __construct(private string $name) {}

    public function getName(): string {
        return $this->name;
    }
}