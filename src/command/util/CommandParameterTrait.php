<?php

namespace pocketcloud\cloud\bridge\command\util;

use InvalidArgumentException;
use pocketcloud\cloud\bridge\CloudBridge;
use Throwable;

trait CommandParameterTrait {

    /** @var array<string, ParameterData> */
    private array $parameters = [];

    public function registerParameter(ParameterData $parameterData, ?int $index = null): void {
        if ($index !== null) {
            $this->parameters[$index] = $parameterData;
        } else {
            $this->parameters[] = $parameterData;
        }
    }

    public function parseArgs(array $args, ?ParameterData &$currentParameter = null): array|false|null {
        $args = array_values($args);
        $parsedArgs = [];
        try {
            foreach ($this->parameters as $i => $parameter) {
                $currentParameter = $parameter;
                if ($parameter->isOptional() && !isset($args[$i])) continue;
                if (isset($args[$i])) {
                    if (!isset($this->parameters[$i + 1]) && $parameter->getType() === ParameterType::STRING) {
                        // last arg, chaining every other arg together, resulting in massive chained string
                        $parsedArgs[$parameter->getName()] = $parameter->parseValue(implode(" ", array_slice($args, $i)));
                        break;
                    } else $parsedArgs[$parameter->getName()] = $parameter->parseValue($args[$i]);
                } else {
                    if (!$parameter->isOptional()) return null;
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

    public function getRequiredParameterCount(): int {
        return count(array_filter($this->parameters, fn(ParameterData $parameterData) => !$parameterData->isOptional()));
    }

    public function getOptionalParameterCount(): int {
        return count(array_filter($this->parameters, fn(ParameterData $parameterData) => $parameterData->isOptional()));
    }

    public function getParameter(int $index): ?ParameterData {
        return $this->parameters[$index] ?? null;
    }

    public function getParameters(): array {
        return $this->parameters;
    }
}