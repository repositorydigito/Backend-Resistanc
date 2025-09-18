<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\TemplateEmail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecoverPasswordCode extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $code;
    public $data;
    public $company;

    /**
     * Create a new message instance.
     */
    public function __construct($code, User $user)
    {
        $this->code = $code;
        $this->user = $user;
        $this->company = Company::first();
        $this->data = TemplateEmail::where('name', 'Código de verificación')->first();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->data->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-code',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
