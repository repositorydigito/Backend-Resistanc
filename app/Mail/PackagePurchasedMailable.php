<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PackagePurchasedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $userPackage;
    public $company;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, UserPackage $userPackage)
    {
        $this->user = $user;
        $this->userPackage = $userPackage;
        $this->company = Company::first();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Tu paquete ya está activo!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.package-purchased',
            with: [
                'user' => $this->user,
                'userPackage' => $this->userPackage,
                'company' => $this->company,
            ]
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

