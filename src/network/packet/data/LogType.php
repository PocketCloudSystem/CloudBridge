<?php

namespace pocketcloud\cloud\bridge\network\packet\data;

use pocketcloud\cloud\bridge\util\trait\EnumHelperTrait;

enum LogType {
    use EnumHelperTrait;

    case INFO;
    case WARN;
    case ERROR;
    case SUCCESS;
    case DEBUG;

    public function getName(): string {
        return $this->name;
    }
}