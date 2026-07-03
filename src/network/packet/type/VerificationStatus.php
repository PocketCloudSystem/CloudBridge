<?php

namespace pocketcloud\cloud\bridge\network\packet\type;

use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\trait\EnumHelperTrait;

enum VerificationStatus implements Writeable {
    use EnumHelperTrait;

    case DENIED;
    case VERIFIED;
    case PENDING;

    public function getName(): string {
        return $this->name;
    }

    public function write(): string {
        return $this->name;
    }
}