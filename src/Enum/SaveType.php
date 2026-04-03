<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Enum;

final class SaveType
{
    /** Збереження без проведення */
    public const SAVE = 0;

    /** Збереження з проведенням */
    public const REGISTER = 1;

    /** Збереження зі скасуванням проведення */
    public const UNREGISTER = 2;

    /** @var int */
    public $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function save(): self
    {
        return new self(self::SAVE);
    }

    public static function register(): self
    {
        return new self(self::REGISTER);
    }

    public static function unregister(): self
    {
        return new self(self::UNREGISTER);
    }
}
