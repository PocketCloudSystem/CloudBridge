<?php

namespace pocketcloud\cloud\bridge\exception;

use Closure;
use ErrorException;
use pocketcloud\cloud\bridge\CloudBridge;
use Throwable;

final class ExceptionHandler {

    public static function tryCatch(Closure $processClosure, ?string $message = null, ?Closure $onExceptionClosure = null, mixed ...$params): mixed {
        set_error_handler(function (int $errno, string $error, string $file, int $line) {
            if (!(error_reporting() & $errno)) {
                return false;
            }

            throw new ErrorException($error, 0, $errno, $file, $line);
        });

        try {
            return $processClosure(...$params);
        } catch (Throwable $exception) {
            if ($message !== null) CloudBridge::getInstance()->getLogger()->error($message);
            ($onExceptionClosure)($exception);
        } finally {
            restore_error_handler();
        }

        return null;
    }
}