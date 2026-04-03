<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Exception;

use Throwable;

/**
 * Помилка API Dilovod (сервер повернув помилку в тілі відповіді).
 */
class ApiException extends DilovodException
{
    /** @var array<mixed>|null */
    private $rawResponse;

    /**
     * @param array<mixed>|null $rawResponse
     */
    public function __construct(
        string $message,
        ?array $rawResponse = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->rawResponse = $rawResponse;
    }

    /**
     * @return array<mixed>|null
     */
    public function getRawResponse(): ?array
    {
        return $this->rawResponse;
    }
}
