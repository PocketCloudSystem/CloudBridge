<?php

namespace pocketcloud\cloud\bridge\command\util;

use Exception;
use InvalidArgumentException;
use pocketcloud\cloud\bridge\api\object\group\ServerGroup;
use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\ServerGroupProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\player\Player;
use pocketmine\Server;

enum ParameterType {

    case STRING;
    case INTEGER;
    case FLOAT;
    case BOOLEAN;
    /**
     * @see CloudServer
     */
    case SERVER;
    /**
     * @see Template
     */
    case TEMPLATE;
    /**
     * @see ServerGroup
     */
    case GROUP;
    /**
     * @see Player
     */
    case PLAYER;
    /**
     * @see CloudPlayer
     */
    case CLOUD_PLAYER;

    public function with(string $name, bool $optional = true): ParameterData {
        return ParameterData::create($name, $optional, $this);
    }

    /**
     * @param mixed $value
     * @return mixed Throws InvalidArgumentException if the given value does not match the required value from the ParameterType
     */
    public function parseValue(mixed $value): mixed {
        switch ($this) {
            case self::STRING:
                return $value;
            case self::INTEGER:
                if (is_numeric($value)) {
                    return intval($value);
                }

                return throw new InvalidArgumentException();
            case self::FLOAT:
                if (is_numeric($value)) {
                    return floatval($value);
                }

                return throw new InvalidArgumentException();
            case self::BOOLEAN:
                return $value == "true" || $value == "1" || $value == "yes";
            case self::SERVER:
                $server = CloudServerProvider::provider()->get($value);
                if ($server === null) return throw new InvalidArgumentException();
                return $server;
            case self::TEMPLATE:
                $template = TemplateProvider::provider()->get($value);
                if ($template === null) return throw new InvalidArgumentException();
                return $template;
            case self::GROUP:
                $group = ServerGroupProvider::provider()->get($value);
                if ($group === null) return throw new InvalidArgumentException();
                return $group;
            case self::PLAYER:
                $player = Server::getInstance()->getPlayerExact($value);
                if ($player === null) return throw new InvalidArgumentException();
                return $player;
            case self::CLOUD_PLAYER:
                $player = CloudPlayerProvider::provider()->get($value);
                if ($player === null) return throw new InvalidArgumentException();
                return $player;
        }

        return null;
    }

    public function getNetworkType(): int {
        return match ($this) {
            self::STRING, self::SERVER, self::CLOUD_PLAYER, self::GROUP, self::TEMPLATE => AvailableCommandsPacket::ARG_TYPE_STRING,
            self::INTEGER => AvailableCommandsPacket::ARG_TYPE_INT,
            self::FLOAT => AvailableCommandsPacket::ARG_TYPE_FLOAT,
            self::BOOLEAN => AvailableCommandsPacket::ARG_FLAG_ENUM,
            self::PLAYER => AvailableCommandsPacket::ARG_TYPE_TARGET,
        };
    }

    public function getEnumName(): ?string {
        return match ($this) {
            self::BOOLEAN => "options",
            default => null
        };
    }

    public function getEnumContent(): ?array {
        return match ($this) {
            self::BOOLEAN => ["true", "false"],
            default => null
        };
    }
}