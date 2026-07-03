<?php

namespace pocketcloud\cloud\bridge\util\mapper;

use Attribute;

/**
 * If a property is marked with this attribute, its serialization is skipped.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Transient {}