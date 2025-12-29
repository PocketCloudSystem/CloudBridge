<?php

namespace pocketcloud\cloud\bridge\network\packet\util;

use ErrorException;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\exception\ExceptionHandler;
use pocketcloud\cloud\bridge\exception\PacketException;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\PacketPool;

final class PacketSerializer {

    /**
     * @throws ErrorException
     */
    public static function encode(CloudboundPacket $packet, bool $encryptionEnabled): ?string {
        return ExceptionHandler::tryCatch(function (CloudboundPacket $packet, bool $encryptionEnabled): string {
            $packet->encode($buffer = new PacketData());
            $stringBuffer = json_encode($buffer, JSON_THROW_ON_ERROR);
            if ($encryptionEnabled) $stringBuffer = zlib_encode($stringBuffer, ZLIB_ENCODING_DEFLATE, 3);
            return $stringBuffer;
        }, "Failed to encode packet: " . $packet->getName(), null, $packet, $encryptionEnabled);
    }

    /**
     * @throws ErrorException
     */
    public static function decode(string $buffer, bool $encryptionEnabled): ?ClientboundPacket {
        if ($buffer == "") return null;
        return ExceptionHandler::tryCatch(function (string $buffer, bool $encryptionEnabled): ?ClientboundPacket {
            $data = json_decode($encryptionEnabled ? zlib_decode($buffer) : $buffer, true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($data)) throw new PacketException("Received buffer is not an array");
            $packetName = $data[0] ?? null;
            if ($packetName === null) throw new PacketException("Received buffer does not contain a valid packet name");
            if (($packet = PacketPool::getInstance()->get($packetName)) !== null) {
                if (!$packet instanceof ClientboundPacket) throw new PacketException("Received packet is not a ClientboundPacket");
                $packet->decode(new PacketData($data));
                return $packet;
            }

            return null;
        }, "Failed to decode packet", fn() => CloudBridge::getInstance()->getLogger()->debug("Packet buffer: " . $buffer), $buffer, $encryptionEnabled);
    }
}