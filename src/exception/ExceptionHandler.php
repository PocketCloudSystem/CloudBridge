<?php

namespace pocketcloud\cloud\bridge\exception;

use Closure;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketmine\errorhandler\ErrorToExceptionHandler;
use Throwable;

final class ExceptionHandler {

    public static function tryCatch(Closure $processClosure, ?string $message = null, ?Closure $onExceptionClosure = null, mixed ...$params): mixed {
        return ErrorToExceptionHandler::trap(function () use($processClosure, $message, $onExceptionClosure, $params): mixed {
            try {
                return $processClosure(...$params);
            } catch (Throwable $exception) {
                if ($message !== null) CloudBridge::getInstance()->getLogger()->error($message);
                CloudBridge::getInstance()->getLogger()->logException($exception);
                if ($onExceptionClosure !== null) ($onExceptionClosure)($exception);
            }

            return true;
        });
    }
}