<?php

namespace App\Filament\Support;

/** Resurs faqat adminga ochiq: moderator uni menyuda ham ko'rmaydi, URL orqali ham ocholmaydi. */
trait AdminOnly
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
