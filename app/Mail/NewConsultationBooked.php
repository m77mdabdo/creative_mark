<?php

namespace App\Mail;

use App\Consultation\BookingPresenter;
use App\Consultation\MailStrings;
use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use App\Models\ConsultationRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Alert to the team for a new booking. Replying goes straight to the client. */
class NewConsultationBooked extends Mailable
{
    public function __construct(public ConsultationRequest $booking) {}

    public function envelope(): Envelope
    {
        $locale = config('consultation.staff_locale');
        $name = str_replace(["\r", "\n"], ' ', $this->booking->name);

        return new Envelope(
            subject: MailStrings::get($locale, 'subject_staff', ['ref' => $this->booking->reference, 'name' => $name]),
            replyTo: [new Address($this->booking->email, $name)],
        );
    }

    public function content(): Content
    {
        $locale = config('consultation.staff_locale');

        return new Content(view: 'mail.consultation-staff', with: [
            'locale' => $locale,
            'title' => MailStrings::get($locale, 'staff_intro'),
            'kicker' => MailStrings::get($locale, 'staff_intro'),
            'preheader' => BookingPresenter::when($this->booking, $locale),
            'when' => BookingPresenter::when($this->booking, $locale),
            'clientRows' => BookingPresenter::client($this->booking, $locale),
            'details' => BookingPresenter::details($this->booking, $locale),
            'dashboardUrl' => ConsultationRequestResource::getUrl('view', ['record' => $this->booking]),
        ]);
    }
}
