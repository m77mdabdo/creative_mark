<?php

namespace App\Consultation;

use App\Models\ConsultationRequest;
use Carbon\CarbonImmutable;

/**
 * Bookable consultation times: working days (Sunday to Thursday) from tomorrow, fixed slots, Riyadh time.
 * A slot is taken while a booking for it is still open (new, contacted or scheduled).
 */
final class Calendar
{
    /** @return list<string> dates (Y-m-d) that can be booked */
    public function dates(): array
    {
        $tz = config('consultation.timezone');
        $day = CarbonImmutable::now($tz)->startOfDay()->addDay();
        $dates = [];
        while (count($dates) < config('consultation.days_ahead')) {
            if (in_array($day->dayOfWeek, config('consultation.working_days'), true)) {
                $dates[] = $day->toDateString();
            }
            $day = $day->addDay();
        }

        return $dates;
    }

    /** @return list<string> */
    public function slots(): array
    {
        return config('consultation.slots');
    }

    /**
     * Taken slots within the bookable window, keyed by date. Contains no personal data.
     *
     * @return array<string, list<string>>
     */
    public function taken(): array
    {
        $dates = $this->dates();

        return ConsultationRequest::query()->holdingSlot()
            ->whereBetween('preferred_date', [reset($dates), end($dates)])
            ->get(['preferred_date', 'preferred_slot'])
            ->groupBy(fn ($r) => $r->preferred_date->toDateString())
            ->map(fn ($rows) => $rows->pluck('preferred_slot')->unique()->values()->all())
            ->all();
    }

    public function isBookable(string $date, string $slot): bool
    {
        return in_array($date, $this->dates(), true) && in_array($slot, $this->slots(), true);
    }

    public function isTaken(string $date, string $slot): bool
    {
        return ConsultationRequest::query()->holdingSlot()
            ->whereDate('preferred_date', $date)->where('preferred_slot', $slot)->exists();
    }
}
