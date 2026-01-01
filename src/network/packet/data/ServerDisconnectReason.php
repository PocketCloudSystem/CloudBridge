<?php

namespace pocketcloud\cloud\bridge\network\packet\data;

use pocketcloud\cloud\bridge\util\trait\EnumHelperTrait;

enum ServerDisconnectReason {
    use EnumHelperTrait;

    case CLOUD_SHUTDOWN;
    case SERVER_SHUTDOWN;

    public function getName(): string {
        return $this->name;
    }
}