<?php

namespace App\Mail;

use App\Consultation\BookingPresenter;
use App\Consultation\MailStrings;
use App\Models\ConsultationRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Confirmation to the client, in the language they booked in. */
class ConsultationReceived extends Mailable
{
    public function __construct(public ConsultationRequest $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: MailStrings::get($this->booking->locale, 'subject_client', ['ref' => $this->booking->reference]),
            replyTo: [new Address(config('mail.from.address'), 'Creative Mark')],
        );
    }

    public function content(): Content
    {
        $locale = $this->booking->locale;

        return new Content(view: 'mail.consultation-client', with: [
            'locale' => $locale,
            'title' => MailStrings::get($locale, 'your_booking'),
            'kicker' => MailStrings::get($locale, 'your_booking'),
            'preheader' => MailStrings::get($locale, 'client_intro'),
            'details' => BookingPresenter::details($this->booking, $locale),
        ]);
    }
}
