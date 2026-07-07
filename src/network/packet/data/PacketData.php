<?php

declare(strict_types=1);

namespace pocketcloud\cloud\bridge\network\packet\data;

use OutOfBoundsException;
use pocketcloud\cloud\bridge\util\Utils;
use RuntimeException;
use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\network\packet\type\ServerCommandExecutionResult;
use pocketcloud\cloud\bridge\util\misc\Writeable;
use UnitEnum;
use ValueError;

final class PacketData {

    private const int TYPE_NULL = 0;
    private const int TYPE_STRING = 1;
    private const int TYPE_INT = 2;
    private const int TYPE_LONG = 3;
    private const int TYPE_FLOAT = 4;
    private const int TYPE_DOUBLE = 5;
    private const int TYPE_BOOLEAN = 6;
    private const int TYPE_ARRAY = 7;
    private const int TYPE_MAP = 8;

    private string $buffer;
    private int $offset = 0;
    private int $elementCount = 0;

    public function __construct(string $buffer = "") {
        $this->buffer = $buffer;
        if ($buffer !== "") {
            $this->elementCount = $this->countRemainingElements();
        }
    }

    public static function fromString(string $buffer): self {
        return new self($buffer);
    }

    public function writeAll(mixed ...$v): void {
        foreach ($v as $item) $this->write($item);
    }

    public function write(mixed $v): self {
        $this->writeValue($v instanceof Writeable ? $v->write() : $v);
        $this->elementCount++;
        return $this;
    }

    private function writeValue(mixed $value): void {
        if ($value === null) {
            $this->buffer .= chr(self::TYPE_NULL);
        } elseif (is_bool($value)) {
            $this->buffer .= chr(self::TYPE_BOOLEAN) . chr($value ? 1 : 0);
        } elseif (is_int($value)) {
            if ($value >= -2147483648 && $value <= 2147483647) {
                $this->buffer .= chr(self::TYPE_INT) . pack("N", $value < 0 ? $value + 0x100000000 : $value);
            } else {
                $this->buffer .= chr(self::TYPE_LONG) . pack("J", $value);
            }
        } elseif (is_float($value)) {
            $this->buffer .= chr(self::TYPE_DOUBLE) . pack("E", $value);
        } elseif (is_string($value)) {
            $this->buffer .= chr(self::TYPE_STRING) . $this->packString($value);
        } elseif (is_array($value)) {
            if (array_is_list($value)) {
                $this->buffer .= chr(self::TYPE_ARRAY) . pack("N", count($value));
                foreach ($value as $item) {
                    $this->writeValue($item instanceof Writeable ? $item->write() : $item);
                }
            } else {
                $this->buffer .= chr(self::TYPE_MAP) . pack("N", count($value));
                foreach ($value as $key => $item) {
                    $this->buffer .= $this->packString((string)$key);
                    $this->writeValue($item instanceof Writeable ? $item->write() : $item);
                }
            }
        } elseif ($value instanceof UnitEnum) {
            $this->buffer .= chr(self::TYPE_STRING) . $this->packString($value->name);
        } else {
            $this->buffer .= chr(self::TYPE_STRING) . $this->packString((string)$value);
        }
    }

    private function packString(string $value): string {
        return pack("N", strlen($value)) . $value;
    }

    public function isEmpty(): bool {
        return $this->offset >= strlen($this->buffer);
    }

    public function read(): mixed {
        if ($this->isEmpty()) throw new OutOfBoundsException("Packet buffer is empty");
        $value = $this->readValue();
        if ($this->elementCount > 0) $this->elementCount--;
        return $value;
    }

    public function readAll(mixed &...$v): void {
        foreach ($v as &$item) {
            if ($this->isEmpty()) throw new OutOfBoundsException("Passed too many references, packet buffer is empty");
            $item = $this->read();
        }
    }

    /**
     * @param array $refs
     * @param array<Closure(PacketData $buffer): mixed> $readers
     * @return void
     */
    public function readAllTypeSafe(array $refs, array $readers = []): void {
        foreach ($refs as $i => &$item) {
            if ($this->isEmpty()) throw new OutOfBoundsException("Passed too many references, packet buffer is empty");
            $reader = $readers[$i] ?? fn(PacketData $buffer) => $buffer->read();
            $item = $reader($this);
        }
    }

    private function consume(int $length): string {
        if ($this->offset + $length > strlen($this->buffer)) {
            throw new OutOfBoundsException("Packet buffer underflow");
        }
        $chunk = substr($this->buffer, $this->offset, $length);
        $this->offset += $length;
        return $chunk;
    }

    private function readValue(): mixed {
        $type = ord($this->consume(1));
        return match ($type) {
            self::TYPE_NULL => null,
            self::TYPE_STRING => $this->readRawString(),
            self::TYPE_INT => $this->readInt32(),
            self::TYPE_LONG => $this->readInt64(),
            self::TYPE_FLOAT => $this->readRawFloat(),
            self::TYPE_DOUBLE => $this->readRawDouble(),
            self::TYPE_BOOLEAN => $this->readRawBool(),
            self::TYPE_ARRAY => $this->readRawArrayList(),
            self::TYPE_MAP => $this->readRawMap(),
            default => throw new RuntimeException("Unknown type tag: $type"),
        };
    }

    private function readUInt32(): int {
        return unpack("N", $this->consume(4))[1];
    }

    private function readInt32(): int {
        $unsigned = $this->readUInt32();
        return $unsigned >= 0x80000000 ? $unsigned - 0x100000000 : $unsigned;
    }

    private function readInt64(): int {
        return unpack("J", $this->consume(8))[1];
    }

    private function readRawFloat(): float {
        return unpack("G", $this->consume(4))[1];
    }

    private function readRawDouble(): float {
        return unpack("E", $this->consume(8))[1];
    }

    private function readRawBool(): bool {
        return ord($this->consume(1)) !== 0;
    }

    private function readRawString(): string {
        $length = $this->readInt32();
        return $length > 0 ? $this->consume($length) : "";
    }

    private function readRawArrayList(): array {
        $size = $this->readInt32();
        $list = [];
        for ($i = 0; $i < $size; $i++) {
            $list[] = $this->readValue();
        }
        return $list;
    }

    private function readRawMap(): array {
        $size = $this->readInt32();
        $map = [];
        for ($i = 0; $i < $size; $i++) {
            $key = $this->readRawString();
            $map[$key] = $this->readValue();
        }
        return $map;
    }

    private function countRemainingElements(): int {
        $savedOffset = $this->offset;
        $count = 0;
        while (!$this->isEmpty()) {
            $this->skipValue();
            $count++;
        }
        $this->offset = $savedOffset;
        return $count;
    }

    private function skipValue(): void {
        $type = ord($this->consume(1));
        switch ($type) {
            case self::TYPE_NULL:
                break;
            case self::TYPE_BOOLEAN:
                $this->consume(1);
                break;
            case self::TYPE_INT:
            case self::TYPE_FLOAT:
                $this->consume(4);
                break;
            case self::TYPE_LONG:
            case self::TYPE_DOUBLE:
                $this->consume(8);
                break;
            case self::TYPE_STRING:
                $length = $this->readInt32();
                $this->consume($length);
                break;
            case self::TYPE_ARRAY:
                $size = $this->readInt32();
                for ($i = 0; $i < $size; $i++) $this->skipValue();
                break;
            case self::TYPE_MAP:
                $size = $this->readInt32();
                for ($i = 0; $i < $size; $i++) {
                    $keyLen = $this->readInt32();
                    $this->consume($keyLen);
                    $this->skipValue();
                }
                break;
            default:
                throw new RuntimeException("Unknown type tag: $type");
        }
    }

    public function readInt(): ?int {
        $read = $this->read();
        if ($read === null) return null;
        return intval($read);
    }

    public function readFloat(): ?float {
        $read = $this->read();
        if ($read === null) return null;
        return floatval($read);
    }

    public function readBool(): ?bool {
        $read = $this->read();
        if ($read === null) return null;
        return boolval($read);
    }

    public function readString(): ?string {
        $read = $this->read();
        if ($read === null) return null;
        return (string) $read;
    }

    public function readArray(): ?array {
        $read = $this->read();
        if (is_array($read)) return $read;
        return null;
    }

    public function readTemplate(): ?Template {
        return Template::read($this->readArray());
    }

    public function readServer(): ?CloudServer {
        return CloudServer::read($this->readArray());
    }

    public function readServerGroup(): ?CloudServer {
        return CloudServer::read($this->readArray());
    }

    public function readPlayer(): ?CloudPlayer {
        return CloudPlayer::read($this->readArray());
    }

    public function readServerCommandExecutionResult(): ?ServerCommandExecutionResult {
        return ServerCommandExecutionResult::read($this->readArray());
    }

    /**
     * @template T of UnitEnum
     * @param class-string<T> $enumClass
     * @return T|null
     */
    public function readEnum(string $enumClass): ?UnitEnum {
        try {
            return Utils::findEnumCase($enumClass, $this->readString());
        } catch (ValueError) {
            return null;
        }
    }

    public function count(): int {
        return $this->elementCount;
    }

    public function getBuffer(): string {
        return $this->buffer;
    }
}