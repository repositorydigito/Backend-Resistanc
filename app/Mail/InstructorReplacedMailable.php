<?php

namespace App\Mail;

use App\Models\ClassSchedule;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InstructorReplacedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $classSchedule;
    public $company;
    public $originalInstructor;
    public $substituteInstructor;

    /**
     * Create a new message instance.
     */
    public function __construct(ClassSchedule $classSchedule)
    {
        $this->classSchedule = $classSchedule;
        $this->company = Company::first();
        $this->originalInstructor = $classSchedule->instructor;
        $this->substituteInstructor = $classSchedule->substituteInstructor;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Different RSTAR, same energy! 🔥',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.instructor-replaced',
            with: [
                'classSchedule' => $this->classSchedule,
                'company' => $this->company,
                'originalInstructor' => $this->originalInstructor,
                'substituteInstructor' => $this->substituteInstructor,
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
