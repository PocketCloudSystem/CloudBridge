<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class TemplateSyncPacket extends CloudPacket implements ClientboundPacket {

    public function __construct(
        private ?Template $template = null,
        private bool $removal = false
    ) {}

    public static function create(Template $template, bool $removal): self {
        return new self($template, $removal);
    }

    public function handle(): void {
        if ($this->removal) TemplateProvider::provider()->remove($this->template);
        else TemplateProvider::provider()->add($this->template);
    }

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->template, &$this->removal], [fn() => $packetData->readTemplate(), fn() => $packetData->readBool()]);
    }

    public function getTemplate(): ?Template {
        return $this->template;
    }

    public function isRemoval(): bool {
        return $this->removal;
    }
}