<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\normal;

use pocketcloud\cloud\bridge\api\CloudAPI;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\registry\Registry;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;

class TemplateSyncPacket extends CloudPacket {

    public function __construct(
        private ?Template $template = null,
        private bool $removal = false
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeTemplate($this->template);
        $packetData->write($this->removal);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->template = $packetData->readTemplate();
        $this->removal = $packetData->readBool();
    }

    public function getTemplate(): ?Template {
        return $this->template;
    }

    public function isRemoval(): bool {
        return $this->removal;
    }

    public function handle(): void {
        if (CloudAPI::templates()->get($this->template->getName()) === null) {
            if (!$this->removal) Registry::registerTemplate($this->template);
        } else {
            if ($this->removal) {
                Registry::unregisterTemplate($this->template->getName());
            } else Registry::updateTemplate($this->template->getName(), $this->template->toArray());
        }
    }
}