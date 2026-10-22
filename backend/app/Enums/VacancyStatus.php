<?php

namespace App\Enums;

enum VacancyStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Closed = 'closed';
    case Found = 'found';
    case Expired = 'expired';
}
