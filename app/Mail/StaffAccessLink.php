<?php

namespace App\Mail;

use App\Consultation\MailStrings;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Invitation for new staff, or a password link for existing staff. The link expires; no password is ever emailed. */
class StaffAccessLink extends Mailable
{
    public function __construct(public User $staff, public string $url, public bool $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: MailStrings::get($this->staff->admin_locale, $this->invitation ? 'staff_invite_subject' : 'staff_link_subject'));
    }

    public function content(): Content
    {
        $locale = in_array($this->staff->admin_locale, ['ar', 'en'], true) ? $this->staff->admin_locale : 'ar';

        return new Content(view: 'mail.staff-access', with: [
            'locale' => $locale, 'title' => MailStrings::get($locale, 'staff_kicker'), 'kicker' => MailStrings::get($locale, 'staff_kicker'),
            'preheader' => MailStrings::get($locale, $this->invitation ? 'staff_invite_intro' : 'staff_link_intro'),
            'name' => $this->staff->name, 'intro' => MailStrings::get($locale, $this->invitation ? 'staff_invite_intro' : 'staff_link_intro'),
            'button' => MailStrings::get($locale, 'staff_button'), 'minutes' => (int) config('auth.passwords.users.expire', 60),
            'url' => $this->url, 'email' => $this->staff->email,
        ]);
    }
}
