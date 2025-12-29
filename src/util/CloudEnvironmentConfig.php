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
        "cloud-language" => null
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

    public static function fetchVariable(string $variable): mixed {
        if (!isset(self::$data[$variable])) throw new RuntimeException("Variable '" . $variable . "' not found");
        return self::$data[$variable];
    }

    public static function getNetworkAddress(): string {
        return self::fetchVariable("cloud-address") ?? throw new RuntimeException("Variable 'cloud-address' should not be null, therefore CloudEnvironmentConfig didn't sync yet");
    }
    
    public static function getNetworkPort(): int {
        return self::fetchVariable("cloud-port") ?? throw new RuntimeException("Variable 'cloud-port' should not be null, therefore CloudEnvironmentConfig didn't sync yet");
    }

    public static function getServerName(): string {
        return self::fetchVariable("server-name") ?? throw new RuntimeException("Variable 'server-name' should not be null, therefore CloudEnvironmentConfig didn't sync yet");
    }

    public static function getServerUuid(): string {
        return self::fetchVariable("server-uuid") ?? throw new RuntimeException("Variable 'server-uuid' should not be null, therefore CloudEnvironmentConfig didn't sync yet");
    }

    public static function getTemplateName(): string {
        return self::fetchVariable("template") ?? throw new RuntimeException("Variable 'template' should not be null, therefore CloudEnvironmentConfig didn't sync yet");
    }

    public static function getCloudPath(): string {
        return self::fetchVariable("cloud-path") ?? throw new RuntimeException("Variable 'cloud-path' should not be null, therefore CloudEnvironmentConfig didn't sync yet");
    }

    public static function getLanguage(): string {
        return self::fetchVariable("cloud-language") ?? throw new RuntimeException("Variable 'cloud-language' should not be null, therefore CloudEnvironmentConfig didn't sync yet");
    }

    public static function isNetworkEncryptionEnabled(): bool {
        return self::fetchVariable("network-encryption") ?? throw new RuntimeException("Variable 'network-encryption' should not be null, therefore CloudEnvironmentConfig didn't sync yet");
    }
}