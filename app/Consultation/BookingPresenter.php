<?php

namespace App\Consultation;

use App\Models\ConsultationRequest;
use App\Models\Country;
use App\Models\Region;
use App\Models\Service;
use App\Support\Locales;
use Carbon\CarbonImmutable;

/** Turns a booking into labelled rows for emails and the dashboard, in a given language. */
final class BookingPresenter
{
    public static function when(ConsultationRequest $b, string $locale): string
    {
        $date = CarbonImmutable::parse($b->preferred_date->toDateString().' '.$b->preferred_slot, config('consultation.timezone'))
            ->locale(Locales::tag($locale));

        return $date->translatedFormat('l j F Y').' · '.$b->preferred_slot.' ('.MailStrings::get($locale, 'riyadh_time').')';
    }

    /** @return list<array{0: string, 1: string}> */
    public static function details(ConsultationRequest $b, string $locale): array
    {
        $m = fn (string $k) => MailStrings::get($locale, $k);
        $service = $b->service ? Service::query()->where('slug', $b->service)->withLocale($locale)->first()?->t('title', $locale) : null;
        $region = $b->region ? Region::query()->where('slug', $b->region)->withLocale($locale)->first()?->t('name', $locale) : null;

        return array_values(array_filter([
            [$m('reference'), $b->reference],
            [$m('preferred_time'), self::when($b, $locale)],
            [$m('meeting_mode'), $m('mode_'.$b->meeting_mode)],
            $service ? [$m('service'), $service] : null,
            $region ? [$m('region'), $region] : null,
            $b->stage ? [$m('stage'), $m('stage_'.$b->stage)] : null,
        ]));
    }

    /** @return list<array{0: string, 1: string}> */
    public static function client(ConsultationRequest $b, string $locale): array
    {
        $m = fn (string $k) => MailStrings::get($locale, $k);
        $country = $b->country && $b->country !== 'xx'
            ? Country::query()->where('code', $b->country)->withLocale($locale)->first()?->t('name', $locale) : null;

        return [
            [$m('name'), $b->name],
            [$m('email'), $b->email],
            [$m('phone'), $b->phone],
            [$m('company'), $b->company ?: $m('not_provided')],
            [$m('country'), $country ?? $m('not_provided')],
            [$m('language'), Locales::ALL[$b->locale]['native'] ?? $b->locale],
        ];
    }
}
