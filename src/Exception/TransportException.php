<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Exception;

use Throwable;

/**
 * Помилка HTTP-транспорту (мережа, таймаут, 5xx).
 */
class TransportException extends DilovodException
{
    /** @var int|null */
    private $httpStatusCode;

    public function __construct(
        string $message,
        ?int $httpStatusCode = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->httpStatusCode = $httpStatusCode;
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }
}
