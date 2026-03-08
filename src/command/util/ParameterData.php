<?php

namespace pocketcloud\cloud\bridge\command\util;

use InvalidArgumentException;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandHardEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;

final readonly class ParameterData {

    private function __construct(
        private string $name,
        private bool $optional,
        private ParameterType $type,
        private array $allowedStrings
    ) {}

    public static function create(string $name, bool $optional, ParameterType $type, array $allowedStrings = []): self {
        return new self($name, $optional, $type, $allowedStrings);
    }

    public function buildCommandParameter(): CommandParameter {
        $enum = null;
        if (($this->type->getNetworkType() & AvailableCommandsPacket::ARG_FLAG_ENUM) && $this->type->getEnumName() !== null) {
            $enum = new CommandHardEnum($this->type->getEnumName(), $this->type->getEnumContent() ?? $this->allowedStrings);
        }

        return CommandParameter::allFields($this->name, $this->type->getNetworkType() | AvailableCommandsPacket::ARG_FLAG_VALID, $this->optional, 0, $enum, null);
    }

    public function parseValue(mixed $value): mixed {
        $value = $this->type->parseValue($value);
        if ($this->type->getNetworkType() & AvailableCommandsPacket::ARG_FLAG_ENUM && is_string($value)) {
            if (!in_array($value, $this->type->getEnumContent() ?? $this->allowedStrings)) throw new InvalidArgumentException();
        }

        return $value;
    }

    public function getName(): string {
        return $this->name;
    }

    public function isOptional(): bool {
        return $this->optional;
    }

    public function getType(): ParameterType {
        return $this->type;
    }

    public function getAllowedStrings(): array {
        return $this->allowedStrings;
    }
}