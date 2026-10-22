<?php

namespace App\Enums;

enum WorkFormat: string
{
    case Hourly = 'hourly';
    case Daily = 'daily';
    case FullTime = 'full_time';
    case LiveIn = 'live_in';
}
