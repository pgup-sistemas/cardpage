<?php

namespace App\Listeners;

use App\Services\PlanService;
use Illuminate\Auth\Events\Verified;

class ActivateTrialOnVerification
{
    public function __construct(private PlanService $planService) {}

    public function handle(Verified $event): void
    {
        $this->planService->activateTrial($event->user);
    }
}
