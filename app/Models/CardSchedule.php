<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardSchedule extends Model
{
    protected $fillable = ['card_id', 'service_name', 'slot_duration', 'is_active'];

    protected $casts = [
        'is_active'     => 'boolean',
        'slot_duration' => 'integer',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(CardScheduleSlot::class)->orderBy('weekday')->orderBy('start_time');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(CardAppointment::class)->orderByDesc('appointment_date');
    }
}
