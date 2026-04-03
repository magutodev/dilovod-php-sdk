<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Exception;

use Throwable;

/**
 * Помилка HTTP-транспорту (мережа, таймаут, 5xx).
 */
class TransportException extends DilovodException
{
    public function __construct(
        string $message,
        private readonly ?int $httpStatusCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }
}
