<?php

namespace pocketcloud\cloud\bridge\api\provider;

use pocketcloud\cloud\bridge\api\CloudAPI;

trait CloudAPIGetProviderTrait {

    public static function provider(): self {
        return CloudAPI::get()->getProvider(self::class);
    }
}