<?php

namespace App\Mail;

use App\Consultation\MailStrings;
use App\Models\NotificationRecipient;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells a new address that it will now receive an email for every consultation booked on the website. */
class RecipientAdded extends Mailable
{
    public function __construct(public NotificationRecipient $recipient, public string $addedBy) {}

    private function mailLocale(): string
    {
        return config('consultation.staff_locale', 'ar');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: MailStrings::get($this->mailLocale(), 'recipient_subject'));
    }

    public function content(): Content
    {
        $locale = $this->mailLocale();

        return new Content(view: 'mail.recipient-added', with: [
            'locale' => $locale, 'title' => MailStrings::get($locale, 'recipient_subject'), 'kicker' => MailStrings::get($locale, 'recipient_kicker'),
            'preheader' => MailStrings::get($locale, 'recipient_subject'), 'name' => $this->recipient->name ?: $this->recipient->email,
            'by' => $this->addedBy, 'email' => $this->recipient->email,
        ]);
    }
}
