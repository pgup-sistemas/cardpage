<x-app-layout>
    <div class="max-w-2xl space-y-6">

        <div>
            <h1 class="text-xl font-semibold text-gray-900">Configurações</h1>
            <p class="text-sm text-gray-500 mt-1">Gerencie suas informações de conta.</p>
        </div>

        @if (session('sucesso'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('sucesso') }}
            </div>
        @endif

        {{-- Informações do perfil --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Informações pessoais</h2>
            <livewire:profile.update-profile-information-form />
        </div>

        {{-- Alterar senha --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Alterar senha</h2>
            <livewire:profile.update-password-form />
        </div>

        {{-- Excluir conta (LGPD) --}}
        <div class="bg-white rounded-xl border border-red-100 p-6" x-data="{ confirmar: false }">
            <h2 class="text-sm font-semibold text-red-700 mb-1">Excluir conta</h2>
            <p class="text-xs text-gray-500 mb-4">
                Todos os seus dados serão apagados permanentemente. Esta ação é irreversível.
            </p>

            <button @click="confirmar = true" x-show="!confirmar"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition flex items-center gap-2">
                <svg data-lucide="trash-2" class="w-4 h-4"></svg>
                Excluir minha conta
            </button>

            <div x-show="confirmar" x-transition class="mt-2">
                <p class="text-sm font-medium text-red-700 mb-3">Confirme sua senha para continuar:</p>
                <form method="POST" action="{{ route('dashboard.settings.account.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <div class="flex items-center gap-3 flex-wrap">
                        <input type="password" name="password"
                               class="flex-1 min-w-[160px] px-3 py-2 text-sm border border-red-300 rounded-lg focus:outline-none focus:border-red-500"
                               placeholder="Sua senha atual" required>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition">
                            Confirmar
                        </button>
                        <button type="button" @click="confirmar = false"
                                class="px-4 py-2 rounded-lg text-sm text-gray-600 hover:text-gray-800">
                            Cancelar
                        </button>
                    </div>
                    @error('password')
                        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                    @enderror
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
