<?php

namespace App\Enums;

enum PriceUnit: string
{
    case Hour = 'hour';
    case Day = 'day';
    case Month = 'month';
}
