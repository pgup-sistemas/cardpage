<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-6">
        <div class="flex items-center justify-center gap-2">
            <svg data-lucide="credit-card" class="w-7 h-7" style="color: #FCBF49;"></svg>
            <span class="text-xl font-semibold" style="color: #003049;">Card</span>
        </div>
        <p class="text-sm text-gray-500 mt-1">Acesse sua conta</p>
    </div>

    @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="login" class="space-y-4">
        {{-- E-mail --}}
        <div>
            <label for="email" class="block text-xs font-medium text-gray-600 mb-1">E-mail</label>
            <input wire:model="form.email"
                   id="email" name="email" type="email"
                   class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:outline-none focus:border-[#003049] focus:ring-1 focus:ring-[#003049] transition"
                   placeholder="voce@email.com" required autofocus autocomplete="username">
            @error('form.email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Senha --}}
        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="password" class="text-xs font-medium text-gray-600">Senha</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate
                       class="text-xs hover:underline" style="color: #003049;">
                        Esqueci minha senha
                    </a>
                @endif
            </div>
            <input wire:model="form.password"
                   id="password" name="password" type="password"
                   class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:outline-none focus:border-[#003049] focus:ring-1 focus:ring-[#003049] transition"
                   placeholder="Sua senha" required autocomplete="current-password">
            @error('form.password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Lembrar --}}
        <div class="flex items-center gap-2">
            <input wire:model="form.remember" id="remember" type="checkbox"
                   class="w-4 h-4 rounded border-gray-300 text-[#003049] focus:ring-[#003049]">
            <label for="remember" class="text-xs text-gray-600">Lembrar de mim</label>
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="w-full py-2.5 rounded-lg text-sm font-semibold text-white transition hover:opacity-90 active:scale-[.98] flex items-center justify-center gap-2"
                style="background-color: #003049;">
            <svg data-lucide="log-in" class="w-4 h-4"></svg>
            Entrar
        </button>

        <p class="text-center text-xs text-gray-500">
            Não tem conta?
            <a href="{{ route('register') }}" wire:navigate class="font-medium hover:underline" style="color: #003049;">
                Criar grátis
            </a>
        </p>
    </form>
</div>
