<?php

namespace pocketcloud\cloudbridge\network\packet\handler\decoder;

use pocketcloud\cloudbridge\network\packet\content\PacketContent;
use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\pool\PacketPool;

class PacketDecoder {

    public static function decode(string $buffer): ?CloudPacket {
        $contents = json_decode($buffer, true);
        if (is_array($contents)) {
            if (isset($contents[0])) {
                $packet = PacketPool::getInstance()->getPacketById($contents[0]);
                if ($packet !== null) {
                    $packet->decode(new PacketContent($contents));
                    return $packet;
                }
            }
        }
        return null;
    }
}