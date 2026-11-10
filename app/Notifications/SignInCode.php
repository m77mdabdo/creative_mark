<?php

namespace App\Notifications;

use App\Mail\SignInCodeMail;
use App\Models\User;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

/** The one-time sign-in code. Sent straight away (not queued): the person is waiting on the code screen. */
class SignInCode extends Notification
{
    public function __construct(#[SensitiveParameter] public string $code, public int $codeExpiryMinutes) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): SignInCodeMail
    {
        return (new SignInCodeMail($notifiable, $this->code, $this->codeExpiryMinutes))->to($notifiable->email, $notifiable->name);
    }
}
