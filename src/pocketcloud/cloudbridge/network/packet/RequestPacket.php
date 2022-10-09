<?php

namespace pocketcloud\cloudbridge\network\packet;

use pocketcloud\cloudbridge\network\packet\content\PacketContent;
use pocketmine\utils\Utils;

class RequestPacket extends CloudPacket {

    private string $requestId;
    private float|int $sentTime;
    private ?\Closure $then = null;
    private ?\Closure $failure = null;

    public function __construct() {
        $this->requestId = uniqid();
        $this->sentTime = microtime(true);
    }

    public function encode(PacketContent $content): void {
        parent::encode($content);
        $content->put($this->requestId);
    }

    public function decode(PacketContent $content): void {
        parent::decode($content);
        $this->requestId = $content->readString();
    }

    public function then(\Closure $closure): self {
        Utils::validateCallableSignature(function(ResponsePacket $responsePacket): void {}, $closure);
        $this->then = $closure;
        return $this;
    }

    public function failure(\Closure $closure): self {
        Utils::validateCallableSignature(function(): void {}, $closure);
        $this->failure = $closure;
        return $this;
    }

    public function getRequestId(): string {
        return $this->requestId;
    }

    public function getSentTime(): float|int {
        return $this->sentTime;
    }

    public function getThenClosure(): ?\Closure {
        return $this->then;
    }

    public function getFailureClosure(): ?\Closure {
        return $this->failure;
    }
}