<?php

namespace pocketcloud\cloudbridge\network\packet\handler;

use GlobalLogger;
use JsonException;
use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\pool\PacketPool;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;
use pocketcloud\cloudbridge\util\GeneralSettings;
use ReflectionClass;

class PacketSerializer {

    public static function encode(CloudPacket $packet): string {
        $packet->encode($buffer = new PacketData());
        try {
            return GeneralSettings::isNetworkEncryptionEnabled() ? base64_encode(json_encode($buffer, JSON_THROW_ON_ERROR)) : json_encode($buffer, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            GlobalLogger::get()->error("§cFailed to encode packet: §e" . (new ReflectionClass($packet))->getShortName());
            GlobalLogger::get()->logException($exception);
        }
        return "";
    }

    public static function decode(string $buffer): ?CloudPacket {
        try {
            if (trim($buffer) == "") return null;
            $data = json_decode((GeneralSettings::isNetworkEncryptionEnabled() ? base64_decode($buffer) : $buffer),  true, flags: JSON_THROW_ON_ERROR);
            if (is_array($data)) {
                if (isset($data[0])) {
                    if (($packet = PacketPool::getInstance()->getPacketById($data[0])) !== null) {
                        $packet->decode(new PacketData($data));
                        return $packet;
                    }
                }
            }
        } catch (JsonException $exception) {
            GlobalLogger::get()->error("§cFailed to decode a packet!");
            GlobalLogger::get()->logException($exception);
        }
        return null;
    }
}