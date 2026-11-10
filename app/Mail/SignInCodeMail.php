<?php

namespace App\Mail;

use App\Consultation\MailStrings;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use SensitiveParameter;

/** The dashboard sign-in code, in the staff member's dashboard language. */
class SignInCodeMail extends Mailable
{
    public function __construct(public User $staff, #[SensitiveParameter] public string $code, public int $minutes) {}

    private function mailLocale(): string
    {
        return in_array($this->staff->admin_locale, ['ar', 'en'], true) ? $this->staff->admin_locale : 'ar';
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: MailStrings::get($this->mailLocale(), 'signin_subject'));
    }

    public function content(): Content
    {
        $locale = $this->mailLocale();

        return new Content(view: 'mail.sign-in-code', with: [
            'locale' => $locale, 'title' => MailStrings::get($locale, 'signin_subject'), 'kicker' => MailStrings::get($locale, 'signin_kicker'),
            'preheader' => MailStrings::get($locale, 'signin_intro'), 'name' => $this->staff->name, 'code' => $this->code, 'minutes' => $this->minutes,
        ]);
    }
}
