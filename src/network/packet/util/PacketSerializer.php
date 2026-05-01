<?php

namespace pocketcloud\cloud\bridge\network\packet\util;


use JsonException;
use LogicException;
use pocketcloud\cloud\bridge\exception\PacketException;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\PacketPool;
use Throwable;

final class PacketSerializer {

    public static function encode(CloudboundPacket $packet, bool $encryptionEnabled, string $authenticationKey): string {
        try {
            $packet->encode($buffer = new PacketData());
            $buffer->write($authenticationKey);
            $stringBuffer = json_encode($buffer, JSON_THROW_ON_ERROR);
            if ($encryptionEnabled) $stringBuffer = zlib_encode($stringBuffer, ZLIB_ENCODING_DEFLATE, 3);
            return $stringBuffer;
        } catch (Throwable $e) {
            throw new PacketException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws PacketException|LogicException
     */
    public static function decode(string $buffer, bool $encryptionEnabled, string $authenticationKey): ?ClientboundPacket {
        if ($buffer == "") return throw new LogicException("Cannot decode an empty buffer");
        try {
            $data = json_decode($encryptionEnabled ? zlib_decode($buffer) : $buffer, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new PacketException($e->getMessage(), $e->getCode(), $e);
        }

        if (!is_array($data)) throw new PacketException("Received buffer is not an array");
        $packetName = $data[0] ?? null;
        if ($packetName === null) throw new PacketException("Received buffer does not contain a valid packet name");
        if (($packet = PacketPool::getInstance()->get($packetName)) !== null) {
            if (!$packet instanceof ClientboundPacket) throw new PacketException("Received packet is not a ClientboundPacket");
            $packet->decode(new PacketData($data));
            $packet->decode($buffer = new PacketData($data));
            if ($buffer->isEmpty()) throw new PacketException("Received packet does not contain an authentication key");
            if (($givenKey = $buffer->readString()) === null) throw new PacketException("Received packet does not contain an authentication key");
            if ($givenKey !== $authenticationKey) throw new PacketException("Received packet does not contain a valid authentication key");
            return $packet;
        }

        return null;
    }
}