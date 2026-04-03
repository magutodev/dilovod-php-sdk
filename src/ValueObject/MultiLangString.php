<?php

declare(strict_types=1);

namespace Maguto\Dilovod\ValueObject;

use JsonSerializable;
use Stringable;

/**
 * Мультимовний рядок Dilovod (uk/ru).
 */
final readonly class MultiLangString implements Stringable, JsonSerializable
{
    public function __construct(
        public ?string $uk = null,
        public ?string $ru = null,
    ) {}

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
            uk: $data['uk'] ?? null,
            ru: $data['ru'] ?? null,
        );
    }

    /**
     * Створити з одного рядка (однакове значення для обох мов).
     */
    public static function fromString(string $value): self
    {
        return new self(uk: $value, ru: $value);
    }

    /**
     * Отримати значення за кодом мови, з fallback на іншу мову.
     */
    public function get(string $lang = 'uk'): ?string
    {
        return match ($lang) {
            'uk' => $this->uk ?? $this->ru,
            'ru' => $this->ru ?? $this->uk,
            default => $this->uk ?? $this->ru,
        };
    }

    /**
     * @return array{uk: string|null, ru: string|null}
     */
    public function jsonSerialize(): array
    {
        return ['uk' => $this->uk, 'ru' => $this->ru];
    }
}
