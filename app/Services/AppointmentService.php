<?php

namespace App\Services;

use App\Models\Card;
use App\Models\CardAppointment;
use App\Models\CardSchedule;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AppointmentService
{
    public function availableSlots(CardSchedule $schedule, Carbon $date): array
    {
        $weekday = (int) $date->dayOfWeek;

        $slots = $schedule->slots->where('weekday', $weekday);
        if ($slots->isEmpty()) {
            return [];
        }

        $confirmed = $schedule->appointments()
            ->where('appointment_date', $date->toDateString())
            ->where('status', 'confirmed')
            ->pluck('appointment_time')
            ->map(fn ($t) => substr($t, 0, 5))
            ->toArray();

        $available = [];

        foreach ($slots as $slot) {
            $start = Carbon::createFromTimeString($slot->start_time);
            $end   = Carbon::createFromTimeString($slot->end_time);

            while ($start->copy()->addMinutes($schedule->slot_duration)->lte($end)) {
                $time = $start->format('H:i');
                if (!in_array($time, $confirmed)) {
                    $available[] = $time;
                }
                $start->addMinutes($schedule->slot_duration);
            }
        }

        return $available;
    }

    public function isSlotAvailable(CardSchedule $schedule, Carbon $date, string $time): bool
    {
        return in_array($time, $this->availableSlots($schedule, $date));
    }

    public function createRequest(Card $card, array $data): CardAppointment
    {
        return $card->schedule->appointments()->create([
            'visitor_name'     => $data['visitor_name'],
            'visitor_email'    => $data['visitor_email'],
            'visitor_phone'    => $data['visitor_phone'] ?? null,
            'appointment_date' => $data['appointment_date'],
            'appointment_time' => $data['appointment_time'],
            'notes'            => $data['notes'] ?? null,
            'status'           => 'pending',
            'token'            => Str::uuid()->toString(),
            'token_expires_at' => now()->addDays(7),
        ]);
    }

    public function confirm(CardAppointment $appointment): void
    {
        $appointment->update(['status' => 'confirmed']);
    }

    public function refuse(CardAppointment $appointment): void
    {
        $appointment->update(['status' => 'refused']);
    }
}
