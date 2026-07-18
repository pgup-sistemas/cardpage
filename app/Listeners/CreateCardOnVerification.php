<?php

namespace App\Listeners;

use App\Models\Card;
use Illuminate\Auth\Events\Verified;

class CreateCardOnVerification
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        Card::firstOrCreate(
            ['user_id' => $user->id],
            [
                'slug'           => $user->slug,
                'display_name'   => $user->name,
                'is_active'      => true,
                'show_watermark' => true,
            ]
        );
    }
}
