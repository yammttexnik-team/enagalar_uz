<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';
}
