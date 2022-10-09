<?php

namespace pocketcloud\cloudbridge\network\packet\handler\encoder;

use pocketcloud\cloudbridge\network\packet\content\PacketContent;
use pocketcloud\cloudbridge\network\packet\CloudPacket;

class PacketEncoder {

    public static function encode(CloudPacket $packet): false|string {
        $content = new PacketContent([]);
        $packet->encode($content);
        return json_encode($content->getContent());
    }
}