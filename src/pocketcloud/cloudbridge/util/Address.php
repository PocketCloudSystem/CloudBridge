<?php

namespace pocketcloud\cloudbridge\util;

use pmmp\thread\ThreadSafe;

class Address extends ThreadSafe {

    public function __construct(private string $address, private int $port) {}

    public function getAddress(): string {
        return $this->address;
    }

    public function getPort(): int {
        return $this->port;
    }

    public function __toString(): string {
        return $this->address . ":" . $this->port;
    }

    public function isLocalHost(): bool {
        $address = $this->address;
        return $address == "127.0.0.1" || $address == "0.0.0.0" || $address == "localhost";
    }
}