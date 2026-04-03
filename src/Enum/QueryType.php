<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Enum;

enum QueryType: string
{
    case SliceLast = 'sliceLast';
    case Balance = 'balance';
    case Turnover = 'turnover';
    case BalanceAndTurnover = 'balanceAndTurnover';
}
