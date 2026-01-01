<?php

namespace pocketcloud\cloud\bridge\network\packet\data;

use pocketcloud\cloud\bridge\util\trait\EnumHelperTrait;

enum ServerErrorReason {
    use EnumHelperTrait;

    case NO_ERROR;
    case TEMPLATE_EXISTENCE;
    case MAX_SERVERS;
    case SERVER_EXISTENCE;

    public function getName(): string {
        return $this->name;
    }
}
