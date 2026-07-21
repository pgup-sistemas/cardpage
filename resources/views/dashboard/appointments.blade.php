<x-app-layout>

<div>
    <div class="mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Agendamentos</h1>
        <p class="text-sm text-gray-500 mt-1">Confirme ou recuse solicitações de agendamento.</p>
    </div>

    @if (!auth()->user()->isPro() && !auth()->user()->isOnTrial())
    <div class="mb-4 flex items-start gap-3 bg-yellow-50 border border-yellow-200 rounded-xl p-4">
        <i data-lucide="lock" class="w-5 h-5 text-yellow-600 shrink-0 mt-0.5"></i>
        <div>
            <p class="text-[13px] font-semibold text-yellow-800">Recurso exclusivo do plano Pro</p>
            <p class="text-[12px] text-yellow-700 mt-0.5">Gerencie os agendamentos recebidos no seu cartão.</p>
            <a href="{{ route('dashboard.plan') }}"
               class="inline-block mt-2 px-4 py-1.5 rounded-[8px] text-white text-[12px] font-medium"
               style="background-color: #D62828;">
                Ver planos
            </a>
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <livewire:schedule.appointment-list />
    </div>
    @endif
</div>

</x-app-layout>
