<?php

namespace App\Enums;

enum NannyProfileStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Hidden = 'hidden';
}
