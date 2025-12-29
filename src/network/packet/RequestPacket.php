<?php

namespace pocketcloud\cloud\bridge\network\packet;

use Closure;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;
use pocketcloud\cloud\bridge\network\request\RequestManager;
use Throwable;

abstract class RequestPacket extends CloudPacket implements CloudboundPacket {

    private string $requestId;
    /** @var array<Closure> */
    private array $thenClosures = [];
    private ?Closure $failure = null;

    /** @internal */
    public function prepare(): void {
        $this->requestId = uniqid();
    }

    public function encode(PacketData $packetData): void {
        parent::encode($packetData);
        $packetData->write($this->requestId);
    }

    public function decode(PacketData $packetData): void {
        parent::decode($packetData);
        $this->requestId = $packetData->readString();
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

    public function then(Closure $closure): self {
        $this->thenClosures[] = $closure;
        return $this;
    }

    public function failure(Closure $closure): self {
        $this->failure = $closure;
        return $this;
    }

    public function getRequestId(): string {
        return $this->requestId;
    }

    final public function handle(): void {}

    public static function makeRequest(mixed ...$args): static {
        return RequestManager::getInstance()->send(new static(...$args));
    }
}