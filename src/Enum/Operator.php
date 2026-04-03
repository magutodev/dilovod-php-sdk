<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Enum;

enum Operator: string
{
    case Equal = '=';
    case NotEqual = '!=';
    case Greater = '>';
    case GreaterOrEqual = '>=';
    case Less = '<';
    case LessOrEqual = '<=';
    case Contains = '%';
    case NotContains = '!%';
    case InList = 'IL';
    case InHierarchy = 'IH';
    case NotInHierarchy = '!IH';
    case InListHierarchy = 'ILH';
    case NotInListHierarchy = '!ILH';
    case InParentList = 'IPL';
}
