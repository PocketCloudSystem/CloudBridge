<?php

namespace pocketcloud\cloud\bridge\network\packet;

use Closure;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;
use pocketcloud\cloud\bridge\network\request\RequestManager;

abstract class RequestPacket extends CloudPacket {

    private string $requestId;
    private int $sentTime;
    /** @var array<Closure> */
    private array $thenClosures = [];
    private ?Closure $failure = null;

    /** @internal */
    public function prepare(): void {
        $this->requestId = uniqid();
        $this->sentTime = time();
    }

    public function encode(PacketData $packetData): void {
        parent::encode($packetData);
        $packetData->write($this->requestId);
    }

    public function decode(PacketData $packetData): void {
        parent::decode($packetData);
        $this->requestId = $packetData->readString();
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

    public function getSentTime(): int {
        return $this->sentTime;
    }

    public function getThenClosures(): array {
        return $this->thenClosures;
    }

    public function getFailureClosure(): ?Closure {
        return $this->failure;
    }

    final public function handle(): void {}

    public static function makeRequest(mixed ...$args): static {
        return RequestManager::getInstance()->sendRequest(new static(...$args));
    }
}