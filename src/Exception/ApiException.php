<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Exception;

use Throwable;

/**
 * Помилка API Dilovod (сервер повернув помилку в тілі відповіді).
 */
class ApiException extends DilovodException
{
    /**
     * @param array<array-key, mixed>|null $rawResponse
     */
    public function __construct(
        string $message,
        private readonly ?array $rawResponse = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function getRawResponse(): ?array
    {
        return $this->rawResponse;
    }
}
