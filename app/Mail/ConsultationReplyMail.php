<?php

namespace App\Mail;

use App\Models\ConsultationReply;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** A team member's reply to a client, written in the dashboard. The text is sent as plain, escaped text. */
class ConsultationReplyMail extends Mailable
{
    public function __construct(public ConsultationReply $reply) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: str_replace(["\r", "\n"], ' ', $this->reply->subject),
            replyTo: [new Address(config('mail.from.address'), 'Creative Mark')],
        );
    }

    public function content(): Content
    {
        $booking = $this->reply->request;

        return new Content(view: 'mail.consultation-reply', with: [
            'locale' => $booking->locale,
            'title' => $this->reply->subject,
            'kicker' => $this->reply->subject,
            'preheader' => mb_strimwidth($this->reply->body, 0, 120, '…'),
            'booking' => $booking,
            'body' => $this->reply->body,
            'sender' => $this->reply->author?->name,
        ]);
    }
}
