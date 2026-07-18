<div class="space-y-4">

    <div class="flex items-center gap-2">
        <select wire:model.live="statusFilter"
                class="border border-gray-200 rounded-[10px] px-3 py-2 text-[13px] focus:outline-none focus:ring-2 focus:ring-[#003049]">
            <option value="">Todos</option>
            <option value="pending">Pendentes</option>
            <option value="confirmed">Confirmados</option>
            <option value="refused">Recusados</option>
        </select>
    </div>

    @if (session('sucesso'))
    <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-[10px] p-3 text-[13px] text-green-800">
        <i data-lucide="check-circle" class="w-4 h-4"></i>
        {{ session('sucesso') }}
    </div>
    @endif

    @forelse ($appointments as $appt)
    <div class="bg-white border border-gray-100 rounded-[12px] p-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    @if ($appt->status === 'pending')
                        <span class="w-2 h-2 rounded-full bg-yellow-400 shrink-0"></span>
                        <span class="text-[11px] font-medium text-yellow-700">Pendente</span>
                    @elseif ($appt->status === 'confirmed')
                        <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                        <span class="text-[11px] font-medium text-green-700">Confirmado</span>
                    @else
                        <span class="w-2 h-2 rounded-full bg-gray-300 shrink-0"></span>
                        <span class="text-[11px] font-medium text-gray-500">Recusado</span>
                    @endif
                </div>
                <p class="text-[14px] font-semibold text-gray-900">{{ $appt->visitor_name }}</p>
                <p class="text-[12px] text-gray-500">{{ $appt->visitor_email }}
                    @if ($appt->visitor_phone) · {{ $appt->visitor_phone }} @endif
                </p>
                <p class="text-[13px] text-gray-700 mt-1 font-medium">
                    {{ $appt->appointment_date->format('d/m/Y') }} às {{ $appt->appointment_time }}
                </p>
                @if ($appt->notes)
                <p class="text-[12px] text-gray-500 mt-1 italic">{{ $appt->notes }}</p>
                @endif
            </div>

            @if ($appt->isPending())
            <div class="flex flex-col gap-2 shrink-0">
                <button wire:click="confirm({{ $appt->id }})"
                        wire:confirm="Confirmar este agendamento?"
                        class="px-3 py-1.5 rounded-[8px] text-[12px] font-medium text-white bg-green-600 hover:bg-green-700">
                    Confirmar
                </button>
                <button wire:click="refuse({{ $appt->id }})"
                        wire:confirm="Recusar este agendamento?"
                        class="px-3 py-1.5 rounded-[8px] text-[12px] font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">
                    Recusar
                </button>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="flex flex-col items-center gap-2 py-12 text-center">
        <i data-lucide="calendar-x" class="w-10 h-10 text-gray-300"></i>
        <p class="text-[14px] font-medium text-gray-600">Nenhum agendamento encontrado</p>
    </div>
    @endforelse

    {{ $appointments->links() }}

</div>
