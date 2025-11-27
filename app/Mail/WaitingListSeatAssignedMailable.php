<?php

namespace App\Mail;

use App\Models\ClassSchedule;
use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WaitingListSeatAssignedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $classSchedule;
    public $seatNumber;
    public $company;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, ClassSchedule $classSchedule, ?string $seatNumber = null)
    {
        $this->user = $user;
        $this->classSchedule = $classSchedule;
        $this->seatNumber = $seatNumber;
        $this->company = Company::first();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Asiento asignado desde lista de espera - Resistanc Studio',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.waiting-list-seat-assigned',
            with: [
                'user' => $this->user,
                'classSchedule' => $this->classSchedule,
                'seatNumber' => $this->seatNumber,
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


