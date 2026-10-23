<?php

namespace App\Filament\Support;

use Closure;
use Filament\Support\Contracts\HasLabel;

/** Bazadagi kodni (masalan "full_time") o'zbekcha nomga aylantiradi. */
class EnumLabel
{
    /** @param class-string<\BackedEnum&HasLabel> $enum */
    public static function of(string $enum, mixed $state): string
    {
        if ($state instanceof HasLabel) {
            return (string) $state->getLabel();
        }

        return (string) ($enum::tryFrom((string) $state)?->getLabel() ?? $state);
    }

    /** @param class-string<\BackedEnum&HasLabel> $enum */
    public static function using(string $enum): Closure
    {
        return fn (mixed $state): string => self::of($enum, $state);
    }
}
