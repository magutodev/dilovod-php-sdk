<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Response;

/**
 * Розпарсена відповідь від Dilovod API.
 *
 * API повертає помилки у двох форматах:
 * - Стандартний: {"error": "...", "clientMessages": [...]}
 * - Спецметоди (call): {"status": "error", "errorMessage": "...", "data": [...]}
 */
final class ResponseData
{
    /** @var array<mixed> */
    private $raw;

    /**
     * @param array<mixed> $raw
     */
    public function __construct(array $raw)
    {
        $this->raw = $raw;
    }

    /**
     * Необроблений масив відповіді.
     *
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * Чи є відповідь помилкою.
     */
    public function isError(): bool
    {
        if (isset($this->raw['error'])) {
            return true;
        }

        return ($this->raw['status'] ?? null) === 'error';
    }

    /**
     * Текст помилки (якщо є).
     */
    public function getError(): ?string
    {
        // Стандартний формат: {"error": "..."}
        if (isset($this->raw['error']) && is_string($this->raw['error'])) {
            return $this->raw['error'];
        }

        // Формат спецметодів (call): {"status": "error", "errorMessage": "..."}
        if (isset($this->raw['errorMessage']) && is_string($this->raw['errorMessage'])) {
            return $this->raw['errorMessage'];
        }

        return null;
    }

    /**
     * Отримати значення за ключем.
     *
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->raw[$key] ?? $default;
    }

    /**
     * Скалярний результат відповіді (для "ok", числового ID і т.д.).
     * Повертає null якщо відповідь — об'єкт, а не скалярне значення.
     *
     * @return bool|float|int|string|null
     */
    public function getScalarResult()
    {
        $result = $this->raw['_result'] ?? null;

        if (is_string($result) || is_int($result) || is_float($result) || is_bool($result)) {
            return $result;
        }

        return null;
    }
}
