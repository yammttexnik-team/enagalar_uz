<?php

namespace App\Enums;

enum LoginRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Consumed = 'consumed';
}
