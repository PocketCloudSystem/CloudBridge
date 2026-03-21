<?php

namespace pocketcloud\cloud\bridge\network\packet\data;

use LogLevel;
use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\trait\EnumHelperTrait;

enum LogType implements Writeable {
    use EnumHelperTrait;

    case INFO;
    case WARN;
    case ERROR;
    case SUCCESS;
    case DEBUG;

    public function getName(): string {
        return $this->name;
    }

    public function toLogLevel(): string {
        return match ($this) {
            self::INFO => LogLevel::INFO,
            self::WARN => LogLevel::WARNING,
            self::ERROR => LogLevel::ERROR,
            self::SUCCESS => LogLevel::NOTICE,
            self::DEBUG => LogLevel::DEBUG
        };
    }

    public function write(): string {
        return $this->name;
    }
}