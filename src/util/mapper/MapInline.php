<?php

namespace pocketcloud\cloud\test;

use Attribute;

/**
 * If a property is marked with this attribute, the property's name will not be put into the serialization array.
 * Instead, all the values inside the property will directly be added to the array.
 * @example
 * ```php
 * <?php
 * class Test {
 *     private Class1 $class1;
 * }
 * ?>
 * ```
 * Serializing (& json_encoding) this with MapperUtils will output something like this:
 * ```json
 * {
 *     "class1": {
 *         ...
 *     }
 * }
 * ```
 * If we use the @see MapInline attribute, it would look like the following:
 * ```php
 * <?php
 * class Test {
 *     #[MapInline]
 *     private Class1 $class1;
 * }
 * ?>
 * ```
* Serializing (& json_encoding) this with MapperUtils will output something like this:
 * ```json
 * {
 *     ... (properties from Class1 directly inside the array)
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class MapInline {}