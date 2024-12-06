<?php

namespace pocketcloud\cloud\bridge\util\net;

use pmmp\thread\ThreadSafe;

class Address extends ThreadSafe {

    public function __construct(
        private readonly string $address,
        private readonly int $port
    ) {}

    public function getAddress(): string {
        return $this->address;
    }

    public function getPort(): int {
        return $this->port;
    }

    public function __toString(): string {
        return $this->address . ":" . $this->port;
    }

    public function isLocal(): bool {
        $address = $this->address;
        return $address == "127.0.0.1";
    }
}