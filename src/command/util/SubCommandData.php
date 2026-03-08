<?php

namespace pocketcloud\cloud\bridge\command\util;

use Closure;
use InvalidArgumentException;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\types\command\CommandHardEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use Throwable;

final readonly class SubCommandData {

    /**
     * @param string $name
     * @param string $description
     * @param Closure(CommandSender $sender, string $commandLabel, array $args): bool $handler
     * @param array<ParameterData> $parameters
     * @param bool $optional
     */
    private function __construct(
        private string $name,
        private string $description,
        private Closure $handler,
        private array $parameters,
        private bool $optional
    ) {}

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        $parsedArgs = $this->parseArgs($args);
        if (!$parsedArgs) return false;
        try {
            return ($this->handler)($sender, $commandLabel, $parsedArgs);
        } catch (Throwable $e) {
            $sender->sendMessage("An error occurred while executing the command: " . $e->getMessage());
            CloudBridge::getInstance()->getLogger()->logException($e);
        }

        return true;
    }

    public function parseArgs(array $args): array|false {
        $args = array_values($args);
        $parsedArgs = [];
        try {
            foreach ($this->parameters as $i => $parameter) {
                if ($parameter->isOptional() && !isset($args[$i])) continue;
                if (isset($args[$i])) {
                    $parsedArgs[$i] = $parameter->parseValue($args[$i]);
                } else {
                    if (!$parameter->isOptional()) return false;
                }
            }
        } catch (InvalidArgumentException) {
            return false;
        } catch (Throwable $e) {
            CloudBridge::getInstance()->getLogger()->logException($e);
            return false;
        }

        return $parsedArgs;
    }

    public function buildUsage(): string {
        $base = "§c/cloud " . $this->name;
        foreach ($this->parameters as $parameter) {
            $base .= " " . ($parameter->isOptional() ? "[" : "<") . $parameter->getName() . ": " . strtolower($parameter->getType()->name) . ($parameter->isOptional() ? "]" : ">") . " §8- §c" . $this->getDescription();
        }

        return $base;
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

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getParameters(): array {
        return $this->parameters;
    }

    public function isOptional(): bool {
        return $this->optional;
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
}