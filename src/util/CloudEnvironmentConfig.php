<?php

namespace pocketcloud\cloud\bridge\util;

use pocketmine\Server;
use pocketmine\utils\Config;
use RuntimeException;
use Symfony\Component\Filesystem\Path;

final class CloudEnvironmentConfig {

    private static array $data = [
        "cloud-address" => null,
        "cloud-port" => null,
        "network-encryption" => null,
        "server-name" => null,
        "server-uuid" => null,
        "template" => null,
        "cloud-path" => null,
        "cloud-language" => null,
        "server-timeout" => null,
        "auth-key" => null
    ];

    private static function serverProperties(): Config {
        return new Config(Path::join(Server::getInstance()->getDataPath(), "server.properties"), Config::PROPERTIES);
    }

    public static function sync(): void {
        $properties = self::serverProperties();
        foreach (array_keys(self::$data) as $property) {
            self::syncVariable($property, $properties);
        }
    }

    public static function syncVariable(string $variable, ?Config $config = null): mixed {
        $config = $config ?? self::serverProperties();
        if (!$config->exists($variable)) throw new RuntimeException("Variable '" . $variable . "' not found inside " . $config->getPath());
        return self::$data[$variable] = $config->get($variable);
    }

    public static function fetchVariable(string $variable, bool $canReturnNull = true): mixed {
        if (!array_key_exists($variable, self::$data)) throw new RuntimeException("Variable '" . $variable . "' does not exist");
        return self::$data[$variable] ?? ($canReturnNull ? null : throw new RuntimeException("Variable '" . $variable . "' should not return null, therefore CloudEnvironmentConfig didn't sync yet"));
    }

    public static function getNetworkAddress(): string {
        return self::fetchVariable("cloud-address", false);
    }
    
    public static function getNetworkPort(): int {
        return self::fetchVariable("cloud-port", false);
    }

    public static function getServerName(): string {
        return self::fetchVariable("server-name", false);
    }

    public static function getServerUuid(): string {
        return self::fetchVariable("server-uuid", false);
    }

    public static function getTemplateName(): string {
        return self::fetchVariable("template", false);
    }

    public static function getCloudPath(): string {
        return self::fetchVariable("cloud-path", false);
    }

    public static function getLanguage(): string {
        return self::fetchVariable("cloud-language", false);
    }

    public static function isNetworkEncryptionEnabled(): bool {
        return self::fetchVariable("network-encryption", false);
    }

    public static function getServerTimeout(): int {
        return self::fetchVariable("server-timeout", false);
    }

    public static function getNetworkAuthKey(): string {
        return self::fetchVariable("auth-key", false);
    }
}