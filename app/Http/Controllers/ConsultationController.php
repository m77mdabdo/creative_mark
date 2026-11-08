<?php

namespace App\Http\Controllers;

use App\Consultation\BookConsultation;
use App\Consultation\Calendar;
use App\Consultation\Notifier;
use App\Content\MediaLibrary;
use App\Http\Requests\BookConsultationRequest;
use App\Models\Country;
use App\Models\Region;
use App\Models\Service;
use App\Translation\UiStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Public consultation booking: form, submission and thank-you page. */
class ConsultationController
{
    public function show(Request $request, Calendar $calendar): Response
    {
        $locale = app()->getLocale();
        $services = Service::query()->live()->orderBy('sort_order')->withLocale($locale)->get();
        $regions = Region::query()->live()->orderBy('sort_order')->withLocale($locale)->get();
        $ui = UiStrings::forLocale($locale);

        return Inertia::render('ConsultationPage', [
            'pageKey' => 'consultation',
            'title' => $ui['bookTitle'],
            'seo' => ['title' => $ui['bookTitle'].' | Creative Mark', 'description' => $ui['bookIntro']],
            'services' => $services->map(fn ($s) => ['slug' => $s->slug, 'title' => $s->t('title', $locale), 'icon' => $s->icon])->all(),
            'regions' => $regions->map(fn ($r) => ['slug' => $r->slug, 'name' => $r->t('name', $locale)])->all(),
            'countries' => Country::query()->live()->orderBy('sort_order')->withLocale($locale)->get()
                ->map(fn ($c) => ['code' => $c->code, 'name' => $c->t('name', $locale)])->all(),
            'dates' => $calendar->dates(),
            'slots' => $calendar->slots(),
            'taken' => $calendar->taken(),
            'modes' => config('consultation.meeting_modes'),
            'stages' => config('consultation.stages'),
            'prefill' => [
                'service' => $services->contains('slug', $request->query('service')) ? $request->query('service') : null,
                'region' => $regions->contains('slug', $request->query('region')) ? $request->query('region') : null,
            ],
            'formToken' => encrypt((string) time()),
            'media' => MediaLibrary::responsive(['guide-towers'], $locale),
        ]);
    }

    public function store(BookConsultationRequest $request, BookConsultation $book, Notifier $notifier): RedirectResponse
    {
        $locale = app()->getLocale();

        // Honeypot: real visitors never see this field. Bots get the normal success page and nothing is stored.
        if (filled($request->input('website'))) {
            return redirect()->route('consultation.thanks', ['locale' => $locale])->with('consultation_ref', 'CM-'.now()->format('ymd').'-0000');
        }

        $this->throttle($request);

        $booking = $book($request->booking(), $locale, $request->ip(), $request->userAgent());

        // Emails go out after the response, so the visitor is not kept waiting on the mail server.
        dispatch(fn () => app(Notifier::class)->bookingCreated($booking->fresh()))->afterResponse();

        return redirect()->route('consultation.thanks', ['locale' => $locale])->with('consultation_ref', $booking->reference);
    }

    public function thanks(Request $request): Response|RedirectResponse
    {
        $reference = $request->session()->get('consultation_ref');
        if (! $reference) {
            return redirect()->route('consultation', ['locale' => app()->getLocale()]);
        }

        return Inertia::render('ConsultationThanks', [
            'pageKey' => 'consultation-thanks', 'reference' => $reference,
            'media' => MediaLibrary::responsive(['guide-boulevard'], app()->getLocale()),
        ]);
    }

    /** At most N bookings per IP per hour and M per email per day. */
    private function throttle(BookConsultationRequest $request): void
    {
        $ipKey = 'consultation-ip:'.hash('sha256', (string) $request->ip());
        $emailKey = 'consultation-email:'.hash('sha256', (string) $request->input('email'));
        if (RateLimiter::tooManyAttempts($ipKey, config('consultation.per_ip_per_hour'))
            || RateLimiter::tooManyAttempts($emailKey, config('consultation.per_email_per_day'))) {
            throw ValidationException::withMessages(['form' => 'too_many']);
        }
        RateLimiter::hit($ipKey, 3600);
        RateLimiter::hit($emailKey, 86400);
    }
}
