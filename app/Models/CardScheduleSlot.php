<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardScheduleSlot extends Model
{
    protected $fillable = ['card_schedule_id', 'weekday', 'start_time', 'end_time'];

    protected $casts = [
        'weekday' => 'integer',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(CardSchedule::class, 'card_schedule_id');
    }

    public function weekdayLabel(): string
    {
        return ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'][$this->weekday] ?? '?';
    }
}
