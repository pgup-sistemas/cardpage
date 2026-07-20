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
                    title="{{ $isActive ? 'Clique para pausar' : 'Clique para ativar no cartão' }}"
                    class="px-4 py-[11px] rounded-[10px] border text-[13px] font-medium flex items-center gap-1.5 transition
                           {{ $isActive ? 'border-green-500 text-green-700 bg-green-50 hover:bg-red-50 hover:text-red-600 hover:border-red-300' : 'border-amber-400 text-amber-700 bg-amber-50 hover:bg-green-50 hover:text-green-700 hover:border-green-400' }}">
                @if ($isActive)
                    <i data-lucide="circle-check" class="w-3.5 h-3.5"></i> Agenda ativa
                @else
                    <i data-lucide="circle-pause" class="w-3.5 h-3.5"></i> Pausada — clique para ativar
                @endif
            </button>
        </div>

    </form>
</div>
