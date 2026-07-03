<?php

namespace pocketcloud\cloud\test;

/**
 * @template T
 * @template R
 */
interface MapKeyConverter {

    /**
     * @param T $obj
     * @return R
     */
    public function toValue(mixed $obj): mixed;

    /**
     * @param R $obj
     * @return T
     */
    public function fromValue(mixed $obj): mixed;
}