<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Enum;

enum Action: string
{
    case GetObject = 'getObject';
    case SaveObject = 'saveObject';
    case SetDelMark = 'setDelMark';
    case Request = 'request';
    case Call = 'call';
    case ListMetadata = 'listMetadata';
    case GetMetadata = 'getMetadata';
    case GetStatistic = 'getStatistic';
}
