<?php

declare(strict_types=1);

namespace Maguto\Dilovod\ValueObject;

use Maguto\Dilovod\Exception\InvalidArgumentException;
use Stringable;

/**
 * ID об'єкта Dilovod.
 * 16-значне число: перші 5 розрядів — тип об'єкта, останні 11 — унікальний номер.
 */
final readonly class ObjectId implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        if (!preg_match('/^\d{16}$/', $value)) {
            throw new InvalidArgumentException(
                \sprintf('ObjectId must be exactly 16 digits, got "%s".', $value),
            );
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Префікс типу об'єкта (перші 5 розрядів).
     */
    public function getPrefix(): string
    {
        return substr($this->value, 0, 5);
    }

    /**
     * Унікальний номер (останні 11 розрядів).
     */
    public function getNumber(): string
    {
        return substr($this->value, 5);
    }

    /**
     * Чи належать два ID до одного типу об'єкта.
     */
    public function isSameType(self $other): bool
    {
        return $this->getPrefix() === $other->getPrefix();
    }
}
