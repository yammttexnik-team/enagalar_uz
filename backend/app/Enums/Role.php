<?php

namespace App\Enums;

enum Role: string
{
    case Parent = 'parent';
    case Nanny = 'nanny';
    case Moderator = 'moderator';
    case Admin = 'admin';
}
