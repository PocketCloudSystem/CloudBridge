<?php

namespace pocketcloud\cloudbridge\module\npc;

use pocketcloud\cloudbridge\api\template\Template;
use pocketmine\world\Position;

class CloudNPC {

    public function __construct(
        private readonly Template $template,
        private readonly Position $position,
        private readonly string $creator
    ) {}

    public function getTemplate(): Template {
        return $this->template;
    }

    public function getPosition(): Position {
        return $this->position;
    }

    public function getCreator(): string {
        return $this->creator;
    }
}