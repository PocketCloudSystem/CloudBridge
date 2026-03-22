<?php

namespace pocketcloud\cloud\bridge\event\network;

use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\util\net\Address;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

final class NetworkPacketReceivePreProcessEvent extends NetworkEvent implements Cancellable {
    use CancellableTrait;

    public function __construct(
        Network $network,
        protected readonly Address $sender,
        protected readonly string $buffer,
        protected readonly bool $encryption
    ) {
        parent::__construct($network);
    }

    public function getSender(): Address {
        return $this->sender;
    }

    public function getBuffer(): string {
        return $this->buffer;
    }

    public function isEncryption(): bool {
        return $this->encryption;
    }
}