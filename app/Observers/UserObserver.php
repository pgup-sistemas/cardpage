<?php

namespace App\Observers;

use App\Models\Card;
use App\Models\User;

class UserObserver
{
    public function updated(User $user): void
    {
        if ($user->wasChanged('email_verified_at') && $user->email_verified_at) {
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
}
