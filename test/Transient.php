<?php

namespace pocketcloud\cloud\test;

use Attribute;

/**
 * If a property is marked with this attribute, its serialization is skipped.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Transient {}