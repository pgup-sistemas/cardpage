<div>
    @if (session('sucesso'))
    <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 rounded-[10px] p-3 text-[13px] text-green-800">
        <i data-lucide="check-circle" class="w-4 h-4"></i>
        {{ session('sucesso') }}
    </div>
    @endif

    <form wire:submit="save" class="space-y-5">

        {{-- Serviço + Duração --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-[12px] font-medium text-gray-700 mb-1">Nome do serviço</label>
                <input wire:model="serviceName" type="text" placeholder="Ex: Consultoria, Reunião..."
                       class="w-full border border-gray-200 rounded-[10px] px-3 py-2.5 text-[13px] focus:outline-none focus:ring-2 focus:ring-[#003049]">
                @error('serviceName') <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[12px] font-medium text-gray-700 mb-1">Duração do slot</label>
                <select wire:model="slotDuration"
                        class="w-full border border-gray-200 rounded-[10px] px-3 py-2.5 text-[13px] focus:outline-none focus:ring-2 focus:ring-[#003049]">
                    <option value="30">30 minutos</option>
                    <option value="60">1 hora</option>
                    <option value="90">1h 30min</option>
                    <option value="120">2 horas</option>
                </select>
            </div>
        </div>

        {{-- Dias da semana --}}
        <div>
            <p class="text-[12px] font-medium text-gray-700 mb-2">Disponibilidade semanal</p>
            <div class="space-y-2">
                @foreach ($weekdayLabels as $idx => $label)
                <div class="flex items-center gap-3 p-2.5 rounded-[10px] border border-gray-100 bg-white">
                    <label class="flex items-center gap-2 cursor-pointer min-w-[48px]">
                        <input type="checkbox" wire:model="availability.{{ $idx }}.ativo"
                               class="rounded border-gray-300">
                        <span class="text-[13px] font-medium text-gray-700">{{ $label }}</span>
                    </label>

                    @if (!empty($availability[$idx]['ativo']))
                    <div class="flex items-center gap-2 ml-auto">
                        <input type="time" wire:model="availability.{{ $idx }}.inicio"
                               class="border border-gray-200 rounded-[8px] px-2 py-1 text-[12px]">
                        <span class="text-[12px] text-gray-400">até</span>
                        <input type="time" wire:model="availability.{{ $idx }}.fim"
                               class="border border-gray-200 rounded-[8px] px-2 py-1 text-[12px]">
                    </div>
                    @else
                    <span class="ml-auto text-[12px] text-gray-400">Indisponível</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Ativar / Salvar --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="flex-1 py-[11px] rounded-[10px] text-white text-[13px] font-medium"
                    style="background-color: #003049;">
                Salvar agenda
            </button>
            <button type="button" wire:click="toggleActive"
                    class="px-4 py-[11px] rounded-[10px] border text-[13px] font-medium {{ $isActive ? 'border-green-500 text-green-700 bg-green-50' : 'border-gray-300 text-gray-600' }}">
                {{ $isActive ? 'Agenda ativa' : 'Agenda pausada' }}
            </button>
        </div>

    </form>
</div>
