<?php

namespace pocketcloud\cloud\bridge\network\packet;

use Closure;
use pocketcloud\cloud\bridge\exception\NetworkException;
use pocketcloud\cloud\bridge\exception\PacketException;
use pocketcloud\cloud\bridge\exception\PacketTooLargeException;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;
use pocketcloud\cloud\bridge\network\request\RequestManager;
use RuntimeException;
use Throwable;

/**
 * The normal request packet sent from sub-servers to the cloud, which will answer through regular ResponsePacket
 * @see ResponsePacket
 */
abstract class RequestPacket extends CloudPacket implements CloudboundPacket {

    private ?string $requestId = null;
    /** @var array<Closure> */
    private array $thenClosures = [];
    private ?Closure $failure = null;

    public static function dynamic(mixed ...$args): static|false {
        return new static(...$args)->sendRequest();
    }

    /**
     * @return RequestPacket
     * @throws NetworkException|PacketException|PacketTooLargeException
     */
    public function sendRequest(): RequestPacket {
        return RequestManager::getInstance()->send($this);
    }

    /** @internal */
    public function prepare(): void {
        if ($this->requestId !== null) return;
        $this->requestId = uniqid();
    }

    final public function encode(PacketData $packetData): void {
        parent::encode($packetData);
        $packetData->write($this->requestId);
    }

    final public function decode(PacketData $packetData): void {
        parent::decode($packetData);
        $this->requestId = $packetData->readString();
    }

    final public function decodePayload(PacketData $packetData): void {}

    /**
     * Should not be used for RequestPackets, use
     * @deprecated
     * @see RequestPacket::sendRequest() instead
     */
    public function sendPacket(): void {
        throw new RuntimeException("Use sendRequest() instead of sendPacket()");
    }

    final public function invokeClosures(bool $failed, ?ResponsePacket $responsePacket, ?RequestPacketFailureReason $reason = null): void {
        if ($failed) {
            if ($this->failure !== null) {
                ($this->failure)($this, null, $reason);
            }
            return;
        }

        $value = null;

        try {
            foreach ($this->thenClosures as $thenClosure) {
                $value = ($thenClosure)($responsePacket, $value);
            }
        } catch (Throwable $exception) {
            if ($this->failure !== null) {
                ($this->failure)($this, $exception, RequestPacketFailureReason::THEN_CRASHED);
            }
        }
    }

    /**
     * @param Closure(ResponsePacket $packet, mixed $initialValue): mixed $closure
     * @return $this
     */
    public function then(Closure $closure): self {
        $this->thenClosures[] = $closure;
        return $this;
    }

    /**
     * @param Closure(RequestPacket $packet, ?Throwable $exception, ?RequestPacketFailureReason $failureReason): mixed $closure
     * @return $this
     */
    public function failure(Closure $closure): self {
        $this->failure = $closure;
        return $this;
    }

    public function isPrepared(): bool {
        return $this->requestId !== null;
    }

    public function getRequestId(): ?string {
        return $this->requestId;
    }

    final public function handle(): void {}
}