<?php

namespace App\Enums;

/** Per-locale translation workflow (LR-06, D-05). */
enum ReviewStatus: string
{
    case Missing = 'missing';
    case Machine = 'machine';
    case Review = 'review';
    case Approved = 'approved';
}
