<div class="space-y-4">

    {{-- Header com limite --}}
    <div class="flex items-center justify-between">
        <p class="text-sm font-semibold text-gray-700 flex items-center gap-2">
            <i data-lucide="image" class="w-4 h-4" style="color: var(--color-primary);"></i>
            Galeria de fotos
        </p>
        <span class="text-xs text-gray-500">{{ $count }} / {{ $isPro ? 30 : 3 }}</span>
    </div>

    @if (!$isPro && $count >= 3)
    <div class="flex items-center gap-3 p-3 bg-amber-50 border border-amber-200 rounded-xl">
        <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 shrink-0"></i>
        <p class="text-xs text-amber-800 flex-1">Limite de 3 fotos no plano Free atingido.</p>
        <a href="{{ route('dashboard.plan') }}"
           class="text-xs font-medium px-2.5 py-1 rounded-lg text-white" style="background-color: var(--color-highlight);">Upgrade</a>
    </div>
    @endif

    {{-- Upload --}}
    @if ($count < $limit)
    <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-gray-400 transition"
         x-data="{ dragging: false }"
         @dragover.prevent="dragging = true"
         @dragleave.prevent="dragging = false"
         @drop.prevent="dragging = false">
        <label class="cursor-pointer block">
            <i data-lucide="upload-cloud" class="w-8 h-8 text-gray-400 mx-auto mb-2"></i>
            <p class="text-sm font-medium text-gray-600">Clique para selecionar ou arraste uma foto</p>
            <p class="text-xs text-gray-400 mt-1">PNG, JPG, WEBP · máx. 5MB</p>
            <input wire:model="newPhoto" type="file" accept="image/*" class="hidden">
        </label>

        @if ($newPhoto)
        <div class="mt-3 flex items-center gap-3 bg-gray-50 rounded-lg p-3">
            <img src="{{ $newPhoto->temporaryUrl() }}" class="w-12 h-12 rounded-lg object-cover">
            <div class="flex-1 text-left">
                <p class="text-xs font-medium text-gray-700 truncate">{{ $newPhoto->getClientOriginalName() }}</p>
                <input wire:model="newCaption" type="text" maxlength="100"
                       placeholder="Legenda (opcional)"
                       class="mt-1 w-full text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none focus:border-blue-500">
            </div>
            <button wire:click="addPhoto"
                    wire:loading.attr="disabled"
                    class="text-xs font-medium px-3 py-1.5 rounded-lg text-white transition hover:opacity-90"
                    style="background-color: var(--color-primary);">
                <span wire:loading.remove>Adicionar</span>
                <span wire:loading>...</span>
            </button>
        </div>
        @endif

        @error('newPhoto')
        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
        @enderror
    </div>
    @endif

    {{-- Grid de fotos --}}
    @if ($photos->isNotEmpty())
    <div id="sortable-photos" class="grid grid-cols-3 gap-2">
        @foreach ($photos as $photo)
        <div data-id="{{ $photo->id }}" class="relative group aspect-square rounded-xl overflow-hidden bg-gray-100 cursor-grab">
            <img src="{{ Storage::url($photo->path) }}"
                 alt="{{ $photo->caption ?? 'Foto' }}"
                 class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition flex items-center justify-center">
                <button wire:click="deletePhoto({{ $photo->id }})"
                        wire:confirm="Excluir esta foto?"
                        class="opacity-0 group-hover:opacity-100 transition text-white bg-red-500/80 rounded-full p-1.5 hover:bg-red-600">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            @if ($photo->caption)
            <div class="absolute bottom-0 left-0 right-0 px-1.5 py-1 bg-black/50">
                <p class="text-white text-[10px] truncate">{{ $photo->caption }}</p>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @else
    <div class="flex flex-col items-center gap-2 py-8 text-center">
        <i data-lucide="image" class="w-10 h-10 text-gray-300"></i>
        <p class="text-sm text-gray-500">Nenhuma foto ainda</p>
    </div>
    @endif

</div>
