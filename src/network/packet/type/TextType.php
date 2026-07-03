<?php

namespace pocketcloud\cloud\bridge\network\packet\type;

use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\trait\EnumHelperTrait;

enum TextType implements Writeable {
    use EnumHelperTrait;

    case MESSAGE;
    case POPUP;
    case TIP;
    case TITLE;
    case ACTION_BAR;
    case TOAST_NOTIFICATION;

    public function getName(): string {
        return $this->name;
    }

    public function write(): string {
        return $this->name;
    }
}