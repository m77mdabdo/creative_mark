<?php

namespace App\Enums;

/** Per-locale translation workflow (LR-06, D-05). */
enum ReviewStatus: string
{
    case Missing = 'missing';
    case Machine = 'machine';
    case Review = 'review';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Missing => __('Missing'),
            self::Machine => __('Machine translation'),
            self::Review => __('Needs review'),
            self::Approved => __('Approved'),
        };
    }
}
