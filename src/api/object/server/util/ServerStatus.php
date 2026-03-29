<?php

namespace pocketcloud\cloud\bridge\api\object\server\util;

use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\trait\EnumHelperTrait;

enum ServerStatus: string implements Writeable {
    use EnumHelperTrait;

    case STARTING = "§2STARTING";
    case ONLINE = "§aONLINE";
    case FULL = "§eFULL";
    case IN_GAME = "§6INGAME";
    case STOPPING = "§4STOPPING";
    case OFFLINE = "§cOFFLINE";

    public function getName(): string {
        return $this->name;
    }

    public function getDisplay(): string {
        return $this->value;
    }

    public function isStarting(): bool {
        return $this === self::STARTING;
    }

    public function isOnline(bool $literal = false): bool {
        return $this === self::ONLINE || (!$literal && ($this === self::FULL || $this === self::IN_GAME));
    }

    public function isFull(): bool {
        return $this === self::FULL;
    }

    public function isInGame(): bool {
        return $this === self::IN_GAME;
    }

    public function isStopping(): bool {
        return $this === self::STOPPING;
    }

    public function isOffline(): bool {
        return $this === self::OFFLINE;
    }

    public function write(): string {
        return $this->name;
    }
}