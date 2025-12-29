<?php

namespace pocketcloud\cloud\bridge\util;

final class Utils {

    public static function containKeys(array $array, string|int ...$keys): bool {
        return array_all($keys, fn(string|int $key) => isset($array[$key]));
    }
}