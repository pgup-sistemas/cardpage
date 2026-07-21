<x-app-layout>
    <div class="max-w-2xl">
        <div class="mb-5">
            <h1 class="text-xl font-semibold text-gray-900">Serviços e Cobrança PIX</h1>
            <p class="text-sm text-gray-500 mt-1">Cadastre seus serviços com valores para receber via PIX diretamente do cartão.</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            @php $card = auth()->user()->card; @endphp
            <livewire:card.service-manager :card="$card" />
        </div>
    </div>
</x-app-layout>
