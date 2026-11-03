<?php

namespace App\Consultation;

use App\Models\ConsultationRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Creates a booking for one slot. A cache lock per slot plus a check inside the transaction means two
 * visitors choosing the same time cannot both get it; the second is asked to pick another slot.
 */
final class BookConsultation
{
    public function __construct(private Calendar $calendar) {}

    /** @param array<string, mixed> $data validated input */
    public function __invoke(array $data, string $locale, ?string $ip, ?string $userAgent): ConsultationRequest
    {
        $date = $data['preferred_date'];
        $slot = $data['preferred_slot'];

        return Cache::lock("consultation-slot:{$date}:{$slot}", 10)->block(5, function () use ($data, $locale, $ip, $userAgent, $date, $slot) {
            return DB::transaction(function () use ($data, $locale, $ip, $userAgent, $date, $slot) {
                if ($this->calendar->isTaken($date, $slot)) {
                    throw ValidationException::withMessages(['preferred_slot' => 'slot_taken']);
                }

                return ConsultationRequest::create([
                    ...$data,
                    'reference' => $this->reference(),
                    'locale' => $locale,
                    'consent_at' => now(),
                    // Keyed hash: lets us spot abuse from one address without storing the address itself.
                    'ip_hash' => $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null,
                    'user_agent' => $userAgent ? Str::limit($userAgent, 250, '') : null,
                ]);
            });
        });
    }

    /** CM-YYMMDD-XXXX with unambiguous characters. */
    private function reference(): string
    {
        do {
            $code = 'CM-'.now(config('consultation.timezone'))->format('ymd').'-'.collect(range(1, 4))
                ->map(fn () => '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'[random_int(0, 31)])->implode('');
        } while (ConsultationRequest::where('reference', $code)->exists());

        return $code;
    }
}
