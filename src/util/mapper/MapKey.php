<?php

namespace pocketcloud\cloud\test;

use Attribute;

/**
 * If a property is marked with this attribute, the $converter controls the serialization and the unserialization of the property and $name sets a custom name.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class MapKey {

    /**
     * @param string $converter
     * @param string $name
     */
    public function __construct(public string $converter, public string $name = "") {}
}