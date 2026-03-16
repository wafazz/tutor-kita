<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TutorVerificationStatus extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $tutor,
        public string $status,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->status === 'verified'
            ? 'Your TutorHUB Account Has Been Approved!'
            : 'TutorHUB Account Verification Update';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.tutor-verification-status',
        );
    }
}
