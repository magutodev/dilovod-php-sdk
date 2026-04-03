<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Enum;

final class Operator
{
    public const EQUAL = '=';
    public const NOT_EQUAL = '!=';
    public const GREATER = '>';
    public const GREATER_OR_EQUAL = '>=';
    public const LESS = '<';
    public const LESS_OR_EQUAL = '<=';
    public const CONTAINS = '%';
    public const NOT_CONTAINS = '!%';
    public const IN_LIST = 'IL';
    public const IN_HIERARCHY = 'IH';
    public const NOT_IN_HIERARCHY = '!IH';
    public const IN_LIST_HIERARCHY = 'ILH';
    public const NOT_IN_LIST_HIERARCHY = '!ILH';
    public const IN_PARENT_LIST = 'IPL';

    /** @var string */
    public $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function equal(): self
    {
        return new self(self::EQUAL);
    }

    public static function notEqual(): self
    {
        return new self(self::NOT_EQUAL);
    }

    public static function greater(): self
    {
        return new self(self::GREATER);
    }

    public static function greaterOrEqual(): self
    {
        return new self(self::GREATER_OR_EQUAL);
    }

    public static function less(): self
    {
        return new self(self::LESS);
    }

    public static function lessOrEqual(): self
    {
        return new self(self::LESS_OR_EQUAL);
    }

    public static function contains(): self
    {
        return new self(self::CONTAINS);
    }

    public static function notContains(): self
    {
        return new self(self::NOT_CONTAINS);
    }

    public static function inList(): self
    {
        return new self(self::IN_LIST);
    }

    public static function inHierarchy(): self
    {
        return new self(self::IN_HIERARCHY);
    }

    public static function notInHierarchy(): self
    {
        return new self(self::NOT_IN_HIERARCHY);
    }

    public static function inListHierarchy(): self
    {
        return new self(self::IN_LIST_HIERARCHY);
    }

    public static function notInListHierarchy(): self
    {
        return new self(self::NOT_IN_LIST_HIERARCHY);
    }

    public static function inParentList(): self
    {
        return new self(self::IN_PARENT_LIST);
    }
}
