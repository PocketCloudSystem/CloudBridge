<?php

namespace pocketcloud\cloud\bridge\command\util;

use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandHardEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;

final readonly class ParameterData {

    private function __construct(
        private string $name,
        private bool $optional,
        private ParameterType $type
    ) {}

    public function buildCommandParameter(): CommandParameter {
        $enum = null;
        if (($this->type->getNetworkType() & AvailableCommandsPacket::ARG_FLAG_ENUM) && $this->type->getEnumName() !== null) {
            $enum = new CommandHardEnum($this->type->getEnumName(), $this->type->getEnumContent() ?? []);
        }

        return CommandParameter::allFields($this->name, $this->type->getNetworkType() | AvailableCommandsPacket::ARG_FLAG_VALID, $this->optional, 0, $enum, null);
    }

    public function parseValue(mixed $value): mixed {
        return $this->type->parseValue($value);
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

    public static function create(string $name, bool $optional, ParameterType $type): self {
        return new self($name, $optional, $type);
    }
}