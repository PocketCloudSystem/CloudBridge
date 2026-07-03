<?php

namespace pocketcloud\cloud\test;

use Attribute;

/**
 * If a property is marked with this attribute, the $converter controls the serialization and the unserialization of the property and $name sets a custom name.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class MapKey {

    /**
     * @param string|null $converter
     * @param string|null $name
     */
    public function __construct(public ?string $converter = null, public ?string $name = null) {}
}