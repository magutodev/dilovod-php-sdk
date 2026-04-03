<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Enum;

enum SaveType: int
{
    /** Збереження без проведення */
    case Save = 0;

    /** Збереження з проведенням */
    case Register = 1;

    /** Збереження зі скасуванням проведення */
    case Unregister = 2;
}
