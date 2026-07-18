<?php

namespace App\Mail;

use App\Models\CardAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CardAppointment $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Card] Nova solicitação de agendamento');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.appointment-requested');
    }
}
