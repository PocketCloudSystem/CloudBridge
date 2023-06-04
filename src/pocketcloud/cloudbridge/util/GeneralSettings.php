<?php

namespace pocketcloud\cloudbridge\util;

use pocketmine\Server;

class GeneralSettings {

    private static array $data = [
        "port" => 3656,
        "encryption" => true,
        "server_name" => "unknown",
        "cloud_path" => "unknown",
        "template_name" => "unknown",
        "language" => "en_US"
    ];

    public static function sync(): void {
        self::$data["port"] = Server::getInstance()->getConfigGroup()->getConfigInt("cloud-port", 3656);
        self::$data["encryption"] = Server::getInstance()->getConfigGroup()->getConfigBool("encryption", true);
        self::$data["server_name"] = Server::getInstance()->getConfigGroup()->getConfigString("server-name", "unknown");
        self::$data["cloud_path"] = Server::getInstance()->getConfigGroup()->getConfigString("cloud-path", "unknown");
        self::$data["template_name"] = Server::getInstance()->getConfigGroup()->getConfigString("template", "unknown");
        self::$data["language"] = Server::getInstance()->getConfigGroup()->getConfigString("cloud-language", "en_US");
    }

    public static function getNetworkPort(): int {
        return self::$data["port"];
    }

    public static function getServerName(): string {
        return self::$data["server_name"];
    }

    public static function getTemplateName(): string {
        return self::$data["template_name"];
    }

    public static function getCloudPath(): string {
        return self::$data["cloud_path"];
    }

    public static function getLanguage(): string {
        return self::$data["language"];
    }

    public static function isNetworkEncryptionEnabled(): bool {
        return self::$data["encryption"];
    }
}