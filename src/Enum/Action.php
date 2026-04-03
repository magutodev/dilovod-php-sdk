<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Enum;

final class Action
{
    public const GET_OBJECT = 'getObject';
    public const SAVE_OBJECT = 'saveObject';
    public const SET_DEL_MARK = 'setDelMark';
    public const REQUEST = 'request';
    public const CALL = 'call';
    public const LIST_METADATA = 'listMetadata';
    public const GET_METADATA = 'getMetadata';
    public const GET_STATISTIC = 'getStatistic';

    /** @var string */
    public $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function getObject(): self
    {
        return new self(self::GET_OBJECT);
    }

    public static function saveObject(): self
    {
        return new self(self::SAVE_OBJECT);
    }

    public static function setDelMark(): self
    {
        return new self(self::SET_DEL_MARK);
    }

    public static function request(): self
    {
        return new self(self::REQUEST);
    }

    public static function call(): self
    {
        return new self(self::CALL);
    }

    public static function listMetadata(): self
    {
        return new self(self::LIST_METADATA);
    }

    public static function getMetadata(): self
    {
        return new self(self::GET_METADATA);
    }

    public static function getStatistic(): self
    {
        return new self(self::GET_STATISTIC);
    }
}
