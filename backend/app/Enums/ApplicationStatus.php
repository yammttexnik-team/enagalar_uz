<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';

    /** Ruxsat etilgan o'tishlar: Rad etilgan — yakuniy holat, "sent"ga qaytib bo'lmaydi. */
    public function canTransitionTo(self $to): bool
    {
        return in_array($to, match ($this) {
            self::Sent => [self::Viewed, self::Shortlisted, self::Rejected],
            self::Viewed => [self::Shortlisted, self::Rejected],
            self::Shortlisted => [self::Rejected],
            self::Rejected => [],
        }, true);
    }
}
