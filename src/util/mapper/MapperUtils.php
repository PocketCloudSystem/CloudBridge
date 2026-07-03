<?php

namespace pocketcloud\cloud\test;

use BackedEnum;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use RuntimeException;
use UnitEnum;
use ValueError;

final class MapperUtils {

    /** @var array<class-string, ReflectionProperty[]> */
    private static array $fieldCache = [];

    /** @var array<class-string, MapKeyConverter<mixed, mixed>> */
    private static array $converterCache = [];

    public static function toMap(object $obj): array {
        try {
            $map = [];
            foreach (self::getProperties($obj::class) as $property) {
                $property->setAccessible(true);

                $mapInline = self::instantiateAttribute($property, MapInline::class);
                $mapKey = self::instantiateAttribute($property, MapKey::class);
                $name = $mapKey !== null ? ($mapKey->name === null ? $property->getName() : $mapKey->name) : $property->getName();

                if (!$property->isInitialized($obj)) {
                    $map[$name] = null;
                    continue;
                }

                $value = $property->getValue($obj);
                if ($value === null) {
                    $map[$name] = null;
                    continue;
                }


                if ($mapInline !== null) {
                    foreach (self::toMap($value) as $k => $v) {
                        $map[$k] = $v;
                    }
                } else if ($mapKey !== null && $mapKey->converter !== null) {
                    $map[$name] = self::getConverter($mapKey->converter)->toValue($value);
                } else {
                    $map[$name] = self::convertToMap($value);
                }
            }

            return $map;
        } catch (ReflectionException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T|null
     */
    public static function fromMap(array $map, string $class): ?object {
        self::checkClass($class);
        try {
            $instance = self::createInstance($class);
            foreach (self::getProperties($class) as $property) {
                $property->setAccessible(true);

                $mapInline = self::instantiateAttribute($property, MapInline::class);
                $mapKey = self::instantiateAttribute($property, MapKey::class);
                $name = $mapKey !== null ? ($mapKey->name === null ? $property->getName() : $mapKey->name) : $property->getName();

                if ($mapInline !== null) {
                    $inlineClass = self::firstClassTypeName($property->getType());
                    if ($inlineClass !== null) {
                        $property->setValue($instance, self::fromMap($map, $inlineClass));
                    }
                } else {
                    if (!array_key_exists($name, $map)) continue;
                    $value = $map[$name];
                    if ($value === null) continue;

                    if ($mapKey !== null && $mapKey->converter !== null) {
                        $property->setValue($instance, self::getConverter($mapKey->converter)->fromValue($value));
                    } else {
                        $property->setValue($instance, self::convertFromMap($value, $property->getType()));
                    }
                }
            }
            return $instance;
        } catch (ReflectionException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     * @throws ReflectionException
     */
    protected static function createInstance(string $class): object {
        self::checkClass($class);
        return new ReflectionClass($class)->newInstanceWithoutConstructor();
    }

    protected static function castValue(mixed $value, string $target): mixed {
        if ($value === null) return null;
        if (self::valueMatchesType($value, $target)) return $value;

        if (enum_exists($target)) {
            try {
                return $target::from($value);
            } catch (ValueError) {
                return $value;
            }
        }

        if (is_numeric($value)) {
            if ($target === "int") return (int) $value;
            if ($target === "float") return (float) $value;
        }

        if (is_string($value)) {
            if ($target === "bool") return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            if ($target === "int") return intval($value);
            if ($target === "float") return floatval($value);
        }

        if ($target === "bool" && is_numeric($value)) {
            return ((float) $value) !== 0.0;
        }

        return $value;
    }

    protected static function convertToMap(mixed $value): mixed {
        if ($value === null) return null;
        if (is_scalar($value)) return $value;

        if ($value instanceof UnitEnum) {
            return $value instanceof BackedEnum ? $value->value : $value->name;
        }

        if (is_array($value)) {
            return array_map(fn($v) => self::convertToMap($v), $value);
        }

        return self::toMap($value);
    }

    protected static function convertFromMap(mixed $value, ?ReflectionType $targetType): mixed {
        if ($value === null) return null;

        $candidates = self::candidateTypeNames($targetType);
        if (empty($candidates)) {
            return $value;
        }

        if (array_any($candidates, fn($candidate) => self::valueMatchesType($value, $candidate))) {
            return $value;
        }

        foreach ($candidates as $candidate) {
            if (enum_exists($candidate)) {
                try {
                    return $candidate::from($value);
                } catch (ValueError) {
                    continue;
                }
            }
        }

        if (is_array($value)) {
            foreach ($candidates as $candidate) {
                if ($candidate === "array") return $value;
                if (class_exists($candidate)) {
                    return self::fromMap($value, $candidate);
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (self::isScalarTypeName($candidate)) {
                return self::castValue($value, $candidate);
            }
        }

        return self::castValue($value, $candidates[0]);
    }

    protected static function getConverter(string $class): MapKeyConverter {
        self::checkClass($class);
        return self::$converterCache[$class] ??= new $class();
    }

    /**
     * @return array<ReflectionProperty>
     * @throws ReflectionException
     */
    protected static function getProperties(string $class): array {
        self::checkClass($class);
        if (isset(self::$fieldCache[$class])) return self::$fieldCache[$class];

        $properties = [];
        foreach ((new ReflectionClass($class))->getProperties() as $property) {
            if (!$property->isStatic() && count($property->getAttributes(Transient::class)) === 0) {
                $properties[] = $property;
            }
        }

        return self::$fieldCache[$class] = $properties;
    }

    protected static function checkClass(string $class): void {
        if (!class_exists($class)) throw new RuntimeException("Class $class does not exist");
    }

    /**
     * @template T of object
     * @param class-string<T> $attributeClass
     * @return T|null
     */
    private static function instantiateAttribute(ReflectionProperty $property, string $attributeClass): ?object {
        $attributes = $property->getAttributes($attributeClass);
        return empty($attributes) ? null : $attributes[0]->newInstance();
    }

    /**
     * @return string[]
     */
    private static function candidateTypeNames(?ReflectionType $type): array {
        if ($type === null) return [];

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $names = [];
            foreach ($type->getTypes() as $t) {
                if ($t instanceof ReflectionNamedType && $t->getName() !== "null") {
                    $names[] = $t->getName();
                }
            }
            return $names;
        }

        if ($type instanceof ReflectionNamedType) {
            return $type->getName() === "null" ? [] : [$type->getName()];
        }

        return [];
    }

    private static function firstClassTypeName(?ReflectionType $type): ?string {
        return array_find(self::candidateTypeNames($type), fn($name) => !self::isScalarTypeName($name) &&
            $name !== "array" &&
            $name !== "mixed" &&
            class_exists($name));
    }

    private static function isScalarTypeName(string $name): bool {
        return in_array($name, ["int", "float", "string", "bool"], true);
    }

    private static function valueMatchesType(mixed $value, string $typeName): bool {
        return match ($typeName) {
            "int" => is_int($value),
            "float" => is_float($value),
            "string" => is_string($value),
            "bool" => is_bool($value),
            "array" => is_array($value),
            "mixed" => true,
            default => $value instanceof $typeName
        };
    }
}