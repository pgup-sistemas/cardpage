<?php

namespace App\Livewire\Schedule;

use App\Models\CardSchedule;
use App\Models\CardScheduleSlot;
use Livewire\Component;

class ScheduleConfig extends Component
{
    public string $serviceName = '';
    public int $slotDuration   = 60;
    public bool $isActive       = false;
    public array $days          = [];

    // Estrutura: [weekday => ['ativo' => bool, 'inicio' => 'HH:MM', 'fim' => 'HH:MM']]
    public array $availability = [];

    protected $rules = [
        'serviceName'                => 'required|string|max:100',
        'slotDuration'               => 'required|integer|in:30,60,90,120',
        'availability.*.ativo'       => 'boolean',
        'availability.*.inicio'      => 'nullable|date_format:H:i',
        'availability.*.fim'         => 'nullable|date_format:H:i',
    ];

    private array $weekdayLabels = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

    public function mount(): void
    {
        $card     = auth()->user()->card;
        $schedule = $card?->schedule;

        for ($i = 0; $i <= 6; $i++) {
            $this->availability[$i] = ['ativo' => false, 'inicio' => '08:00', 'fim' => '18:00'];
        }

        if ($schedule) {
            $this->serviceName  = $schedule->service_name;
            $this->slotDuration = $schedule->slot_duration;
            $this->isActive     = $schedule->is_active;

            foreach ($schedule->slots as $slot) {
                $this->availability[$slot->weekday] = [
                    'ativo'  => true,
                    'inicio' => substr($slot->start_time, 0, 5),
                    'fim'    => substr($slot->end_time, 0, 5),
                ];
            }
        }
    }

    public function save(): void
    {
        $this->validate();

        $card     = auth()->user()->card;
        $schedule = CardSchedule::updateOrCreate(
            ['card_id' => $card->id],
            [
                'service_name'  => $this->serviceName,
                'slot_duration' => $this->slotDuration,
                'is_active'     => $this->isActive,
            ]
        );

        $schedule->slots()->delete();

        foreach ($this->availability as $weekday => $config) {
            if (!empty($config['ativo']) && $config['inicio'] && $config['fim']) {
                CardScheduleSlot::create([
                    'card_schedule_id' => $schedule->id,
                    'weekday'          => $weekday,
                    'start_time'       => $config['inicio'],
                    'end_time'         => $config['fim'],
                ]);
            }
        }

        session()->flash('sucesso', 'Agenda salva com sucesso!');
    }

    public function toggleActive(): void
    {
        $card     = auth()->user()->card;
        $schedule = $card->schedule;

        if ($schedule) {
            $this->isActive = !$this->isActive;
            $schedule->update(['is_active' => $this->isActive]);
        }
    }

    public function render()
    {
        return view('livewire.schedule.schedule-config', [
            'weekdayLabels' => $this->weekdayLabels,
        ]);
    }
}
