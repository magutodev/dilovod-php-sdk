<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Enum;

final class QueryType
{
    public const SLICE_LAST = 'sliceLast';
    public const BALANCE = 'balance';
    public const TURNOVER = 'turnover';
    public const BALANCE_AND_TURNOVER = 'balanceAndTurnover';

    /** @var string */
    public $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function sliceLast(): self
    {
        return new self(self::SLICE_LAST);
    }

    public static function balance(): self
    {
        return new self(self::BALANCE);
    }

    public static function turnover(): self
    {
        return new self(self::TURNOVER);
    }

    public static function balanceAndTurnover(): self
    {
        return new self(self::BALANCE_AND_TURNOVER);
    }
}
