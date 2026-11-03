<?php

namespace App\Enums;

/** Lifecycle of a consultation booking. "Open" statuses hold their time slot. */
enum ConsultationStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => __('New'),
            self::Contacted => __('Contacted'),
            self::Scheduled => __('Scheduled'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
            self::Spam => __('Spam'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Contacted => 'info',
            self::Scheduled => 'success',
            self::Completed => 'gray',
            self::Cancelled, self::Spam => 'danger',
        };
    }

    /** @return list<string> */
    public static function holdingSlot(): array
    {
        return [self::New->value, self::Contacted->value, self::Scheduled->value];
    }
}
