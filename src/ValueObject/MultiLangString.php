<?php

declare(strict_types=1);

namespace Maguto\Dilovod\ValueObject;

use JsonSerializable;

/**
 * Мультимовний рядок Dilovod (uk/ru).
 */
final class MultiLangString implements JsonSerializable
{
    /** @var string|null */
    public $uk;

    /** @var string|null */
    public $ru;

    public function __construct(?string $uk = null, ?string $ru = null)
    {
        $this->uk = $uk;
        $this->ru = $ru;
    }

    public function __toString(): string
    {
        return $this->uk ?? $this->ru ?? '';
    }

    /**
     * @param array{uk?: string|null, ru?: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['uk'] ?? null,
            $data['ru'] ?? null
        );
    }

    /**
     * Створити з одного рядка (однакове значення для обох мов).
     */
    public static function fromString(string $value): self
    {
        return new self($value, $value);
    }

    /**
     * Отримати значення за кодом мови, з fallback на іншу мову.
     */
    public function get(string $lang = 'uk'): ?string
    {
        switch ($lang) {
            case 'uk':
                return $this->uk ?? $this->ru;
            case 'ru':
                return $this->ru ?? $this->uk;
            default:
                return $this->uk ?? $this->ru;
        }
    }

    /**
     * @return array{uk: string|null, ru: string|null}
     */
    public function jsonSerialize(): array
    {
        return ['uk' => $this->uk, 'ru' => $this->ru];
    }
}
