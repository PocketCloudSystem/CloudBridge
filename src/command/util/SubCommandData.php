<?php

namespace pocketcloud\cloud\bridge\command\util;

use Closure;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\types\command\CommandHardEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use Throwable;

final class SubCommandData {
    use CommandParameterTrait;

    /**
     * @param string $name
     * @param string $description
     * @param Closure(CommandSender $sender, string $commandLabel, array $args): bool $handler
     * @param array<ParameterData> $parameters
     * @param bool $optional
     */
    private function __construct(
        private readonly string $name,
        private readonly string $description,
        private readonly Closure $handler,
        array $parameters,
        private readonly bool $optional
    ) {
        $this->parameters = $parameters;
    }

    /**
     * @param string $name
     * @param string $description
     * @param Closure(CommandSender $sender, string $commandLabel, array $args): bool $handler
     * @param array<ParameterData> $parameters
     * @param bool $optional
     * @return SubCommandData
     */
    public static function create(string $name, string $description, Closure $handler, array $parameters = [], bool $optional = false): self {
        return new self($name, $description, $handler, $parameters, $optional);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        try {
            return ($this->handler)($sender, $commandLabel, $args);
        } catch (Throwable $e) {
            $sender->sendMessage("An error occurred while executing the command: " . $e->getMessage());
            CloudBridge::getInstance()->getLogger()->logException($e);
        }

        return true;
    }

    public function isOptional(): bool {
        return $this->optional;
    }

    public function buildUsage(): string {
        $base = LanguageKey::INGAME_PREFIX() .  "§c/cloud " . $this->name;
        foreach ($this->parameters as $parameter) {
            $base .= " " .
                ($parameter->isOptional() ? "[" : "<") .
                $parameter->getName() .
                ": " .
                strtolower($parameter->getType()->name) .
                ($parameter->isOptional() ? "]" : ">") .
                " §8- §c" .
                $this->getDescription();
        }

        return $base;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function buildOverload(): CommandOverload {
        $params = [CommandParameter::enum(
            $this->name,
            new CommandHardEnum($this->name, [$this->name]),
            0
        )];

        foreach ($this->parameters as $parameter) $params[] = $parameter->buildCommandParameter();

        return new CommandOverload(
            false,
            $params
        );
    }
}