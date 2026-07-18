<?php

namespace App\Jobs;

use App\Mail\AppointmentRequestedMail;
use App\Models\CardAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAppointmentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public CardAppointment $appointment) {}

    public function handle(): void
    {
        $card = $this->appointment->schedule->card;
        $ownerEmail = $card->user->email;

        Mail::to($ownerEmail)->send(new AppointmentRequestedMail($this->appointment));
    }
}
