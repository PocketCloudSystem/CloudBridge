<?php

namespace pocketcloud\cloudbridge\api\registry;

use pocketcloud\cloudbridge\api\object\player\CloudPlayer;
use pocketcloud\cloudbridge\api\object\server\CloudServer;
use pocketcloud\cloudbridge\api\object\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\object\template\Template;

class Registry {

    /** @var array<CloudServer> */
    private static array $servers = [];
    /** @var array<Template> */
    private static array $templates = [];
    /** @var array<CloudPlayer> */
    private static array $players = [];

    public static function registerServer(CloudServer $server): void {
        if (!isset(self::$servers[$server->getName()])) self::$servers[$server->getName()] = $server;
    }

    public static function registerTemplate(Template $template): void {
        if (!isset(self::$templates[$template->getName()])) self::$templates[$template->getName()] = $template;
    }

    public static function registerPlayer(CloudPlayer $player): void {
        if (!isset(self::$players[$player->getName()])) self::$players[$player->getName()] = $player;
    }

    public static function unregisterServer(string $server): void {
        if (isset(self::$servers[$server])) unset(self::$servers[$server]);
    }

    public static function unregisterTemplate(string $template): void {
        if (isset(self::$templates[$template])) unset(self::$templates[$template]);
    }

    public static function unregisterPlayer(string $player): void {
        if (isset(self::$players[$player])) unset(self::$players[$player]);
    }

    public static function updateServer(string $server, ServerStatus $serverStatus): void {
        $server = self::$servers[$server] ?? null;
        if ($server === null) return;
        $server->setServerStatus($serverStatus);
    }

    public static function updateTemplate(string $template, array $newData): void {
        $template = self::$templates[$template] ?? null;
        if ($template === null) return;
        $template->apply($newData);
    }

    public static function updatePlayer(string $player, ?string $newServer): void {
        $player = self::$players[$player] ?? null;
        if ($player === null) return;
        $player->setCurrentServer((self::$servers[$newServer] ?? null));
    }

    public static function getServers(): array {
        return self::$servers;
    }

    public static function getTemplates(): array {
        return self::$templates;
    }

    public static function getPlayers(): array {
        return self::$players;
    }
}