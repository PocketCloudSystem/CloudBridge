<?php

namespace pocketcloud\cloud\bridge\network\packet\data;

use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\trait\EnumHelperTrait;

enum ServerDisconnectReason implements Writeable {
    use EnumHelperTrait;

    case CLOUD_SHUTDOWN;
    case SERVER_SHUTDOWN;

    public function getName(): string {
        return $this->name;
    }

    public function write(): string {
        return $this->name;
    }
}