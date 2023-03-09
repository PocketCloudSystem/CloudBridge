<?php

namespace pocketcloud\cloudbridge\utils;

class Address extends \ThreadedBase {

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
}