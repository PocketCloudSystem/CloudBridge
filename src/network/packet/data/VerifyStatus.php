<?php

namespace pocketcloud\cloud\bridge\network\packet\data;

use pocketcloud\cloud\bridge\util\trait\EnumHelperTrait;

enum VerifyStatus {
    use EnumHelperTrait;

    case DENIED;
    case VERIFIED;
    case NOT_APPLIED;

    public function getName(): string {
        return $this->name;
    }
}