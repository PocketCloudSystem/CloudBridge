<?php

namespace pocketcloud\cloud\bridge\command\util;

use InvalidArgumentException;
use pocketcloud\cloud\bridge\api\cache\InGameModuleCache;
use pocketcloud\cloud\bridge\api\object\group\ServerGroup;
use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\ServerGroupProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\ModuleManager;
use pocketcloud\cloud\bridge\network\packet\type\TextType;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\UpdateSoftEnumPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use UnitEnum;

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
     * This will only show servers as it is currently only used for /cloud stop
     */
    case COMBINED_SERVER_TEMPLATE_GROUP;
    /**
     * This will only show templates.
     */
    case COMBINED_TEMPLATE_GROUP;
    /**
     * @see Player
     */
    case PLAYER;
    /**
     * @see CloudPlayer
     */
    case CLOUD_PLAYER;
    /**
     * @see self::with(..., ..., [test1, test2, ...])
     */
    case ENUM;
    case MODULE;
    case TEXT_TYPE;

    public function with(string $name, bool $optional = true, array $allowedStrings = []): ParameterData {
        return ParameterData::create($name, $optional, $this, $allowedStrings);
    }

    /**
     * @param mixed $value
     * @return mixed Throws InvalidArgumentException if the given value does not match the required value from the ParameterType
     */
    public function parseValue(mixed $value): mixed {
        switch ($this) {
            case self::MODULE:
                $module = ModuleManager::getInstance()->get($value);
                if ($module !== null) return $module;
                return throw new InvalidArgumentException();
            case self::TEXT_TYPE:
                $textType = TextType::fromName($value);
                if ($textType !== null) return $textType;
                return throw new InvalidArgumentException();
            case self::ENUM:
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
            case self::COMBINED_SERVER_TEMPLATE_GROUP:
                $obj = CloudServerProvider::provider()->get($value) ?? TemplateProvider::provider()->get($value) ?? ServerGroupProvider::provider()->get($value) ?? null;
                if ($obj === null) return throw new InvalidArgumentException();
                return $obj;
            case self::COMBINED_TEMPLATE_GROUP:
                $obj = TemplateProvider::provider()->get($value) ?? ServerGroupProvider::provider()->get($value) ?? null;
                if ($obj === null) return throw new InvalidArgumentException();
                return $obj;
        }

        return null;
    }

    public function isSoftEnum(): bool {
        return match ($this) {
            self::SERVER, self::TEMPLATE, self::GROUP, self::COMBINED_SERVER_TEMPLATE_GROUP => true,
            default => false
        };
    }

    public function getNetworkType(): int {
        return match ($this) {
            self::STRING, self::CLOUD_PLAYER => AvailableCommandsPacket::ARG_TYPE_STRING,
            self::INTEGER => AvailableCommandsPacket::ARG_TYPE_INT,
            self::FLOAT => AvailableCommandsPacket::ARG_TYPE_FLOAT,
            self::ENUM, self::TEXT_TYPE, self::MODULE, self::BOOLEAN, self::SERVER, self::GROUP, self::TEMPLATE, self::COMBINED_SERVER_TEMPLATE_GROUP, self::COMBINED_TEMPLATE_GROUP => AvailableCommandsPacket::ARG_FLAG_ENUM,
            self::PLAYER => AvailableCommandsPacket::ARG_TYPE_TARGET
        };
    }

    public function getEnumName(): ?string {
        return match ($this) {
            self::ENUM => "options",
            self::BOOLEAN => "choices",
            self::TEXT_TYPE => "text_types",
            self::MODULE => "modules",
            self::TEMPLATE => "templates",
            self::GROUP => "server_groups",
            self::SERVER, self::COMBINED_SERVER_TEMPLATE_GROUP => "servers",
            default => null
        };
    }

    public function generateEnumContent(): ?array {
        return match ($this) {
            self::TEMPLATE, self::COMBINED_TEMPLATE_GROUP => array_map(fn(Template $t) => strtolower($t->getName()), TemplateProvider::provider()->getAll()),
            self::SERVER, self::COMBINED_SERVER_TEMPLATE_GROUP => array_map(fn(CloudServer $s) => strtolower($s->getName()), CloudServerProvider::provider()->getAll()),
            self::GROUP => array_map(fn(ServerGroup $g) => strtolower($g->getName()), ServerGroupProvider::provider()->getAll()),
            default => null
        };
    }

    public function getEnumContent(): ?array {
        return match ($this) {
            self::BOOLEAN => ["true", "false"],
            self::TEXT_TYPE => array_map(fn(UnitEnum $e) => strtolower($e->name), TextType::cases()),
            self::MODULE => array_map(fn(string $s) => strtolower($s), InGameModuleCache::getAll()),
            self::TEMPLATE, self::SERVER, self::GROUP, self::COMBINED_SERVER_TEMPLATE_GROUP => $this->generateEnumContent(),
            default => null
        };
    }

    public function getErrorMessage(): ?string {
        return match ($this) {
            self::INTEGER => LanguageKey::INGAME_PREFIX() . "§cThe value must be a valid integer!",
            self::FLOAT => LanguageKey::INGAME_PREFIX() . "§cThe value must be a valid number!",
            self::BOOLEAN => LanguageKey::INGAME_PREFIX() . "§cThe value must be true or false!",
            self::SERVER => LanguageKey::INGAME_SERVER_NOT_FOUND(),
            self::TEMPLATE => LanguageKey::INGAME_TEMPLATE_NOT_FOUND(),
            self::GROUP => LanguageKey::INGAME_PREFIX() . "§cServer group not found!",
            self::COMBINED_SERVER_TEMPLATE_GROUP => LanguageKey::INGAME_PREFIX() . "§cArgument must either be a server, template or group but none given!",
            self::COMBINED_TEMPLATE_GROUP => LanguageKey::INGAME_PREFIX() . "§cArgument must either be a server or a server group but none given!",
            self::PLAYER, self::CLOUD_PLAYER => LanguageKey::INGAME_PLAYER_NOT_FOUND(),
            self::MODULE => LanguageKey::INGAME_PREFIX() . "§cModule not found!",
            self::TEXT_TYPE => LanguageKey::INGAME_PREFIX() . "§cInvalid text type!",
            self::ENUM => LanguageKey::INGAME_PREFIX() . "§cInvalid option!",
            default => null
        };
    }

    public static function updateEnum(ParameterType $type): void {
        if (!$type->isSoftEnum()) return;

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $player->getNetworkSession()->sendDataPacket(UpdateSoftEnumPacket::create($type->getEnumName(), $type->generateEnumContent(), UpdateSoftEnumPacket::TYPE_SET));
        }
    }
}