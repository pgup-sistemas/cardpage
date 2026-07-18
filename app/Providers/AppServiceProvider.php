<?php

namespace App\Providers;

use App\Listeners\ActivateTrialOnVerification;
use App\Listeners\CreateCardOnVerification;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        User::observe(UserObserver::class);

        Event::listen(Verified::class, ActivateTrialOnVerification::class);
        Event::listen(Verified::class, CreateCardOnVerification::class);
    }
}
