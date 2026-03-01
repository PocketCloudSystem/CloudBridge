<?php

namespace pocketcloud\cloud\bridge\network\packet\data;

use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\trait\EnumHelperTrait;

enum ServerErrorReason implements Writeable {
    use EnumHelperTrait;

    case NO_ERROR;
    case TEMPLATE_EXISTENCE;
    case MAX_SERVERS;
    case SERVER_EXISTENCE;
    case REQUEST_TIMEOUT;

    public function getName(): string {
        return $this->name;
    }

    public function write(): string {
        return $this->name;
    }
}
