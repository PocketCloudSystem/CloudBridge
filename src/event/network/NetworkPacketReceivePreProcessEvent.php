<?php

namespace pocketcloud\cloud\bridge\event\impl\network;

use pocketcloud\cloud\bridge\util\net\Address;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

final class NetworkPacketReceivePreProcessEvent extends NetworkEvent implements Cancellable {
    use CancellableTrait;

    public function __construct(
        private readonly string $buffer,
        private readonly bool $encryption,
        Address $sender
    ) {
        parent::__construct($sender);
    }

    public function getBuffer(): string {
        return $this->buffer;
    }

    public function isEncryption(): bool {
        return $this->encryption;
    }
}