<?php

namespace App\Enums;

/** Enaga qaysi yoshdagi bolalar bilan ishlaydi. */
enum AgeGroup: string
{
    case Infant = 'infant';       // 0-1 yosh
    case Toddler = 'toddler';     // 1-3 yosh
    case Preschool = 'preschool'; // 3-6 yosh
    case School = 'school';       // 6+ yosh
}
