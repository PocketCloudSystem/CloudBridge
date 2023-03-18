<?php

namespace pocketcloud\cloudbridge\network\packet\impl\types;

use pocketmine\utils\RegistryTrait;

/**
 * @method static VerifyStatus NOT_VERIFIED()
 * @method static VerifyStatus VERIFIED()
 */
final class VerifyStatus {
    use RegistryTrait;

    protected static function setup(): void {
        self::_registryRegister("NOT_VERIFIED", new VerifyStatus("NOT_VERIFIED"));
        self::_registryRegister("VERIFIED", new VerifyStatus("VERIFIED"));
    }

    public static function getStatusByName(string $name): ?VerifyStatus {
        $status = self::_registryFromString($name);
        if ($status instanceof VerifyStatus) return $status;
        return null;
    }

    public function __construct(private string $name) {}

    public function getName(): string {
        return $this->name;
    }
}