<?php

namespace pocketcloud\cloud\bridge\network\packet\codec;

use LogicException;
use pocketcloud\cloud\bridge\exception\PacketException;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;
use pocketcloud\cloud\bridge\network\packet\PacketPool;
use Throwable;

final class PacketSerializer {

    public static function encode(CloudboundPacket $packet, bool $encryptionEnabled, string $authenticationKey): string {
        try {
            $packet->encode($buffer = new PacketData());
            $buffer->write($authenticationKey);

            $stringBuffer = $buffer->getBuffer();
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
        $data = PacketData::fromString($encryptionEnabled ? zlib_decode($buffer) : $buffer);
        $packetName = $data->readString();
        if ($packetName === null) throw new PacketException("Received buffer does not contain a valid packet name");
        if (($packet = PacketPool::getInstance()->get($packetName)) !== null) {
            if (!$packet instanceof ClientboundPacket) throw new PacketException("Received packet is not a ClientboundPacket");
            $packet->decode($data);
            if ($data->isEmpty()) throw new PacketException("Received packet does not contain an authentication key");
            if (($givenKey = $data->readString()) === null) throw new PacketException("Received packet does not contain an authentication key");
            if ($givenKey !== $authenticationKey) throw new PacketException("Received packet does not contain a valid authentication key");
            return $packet;
        }

        return null;
    }
}