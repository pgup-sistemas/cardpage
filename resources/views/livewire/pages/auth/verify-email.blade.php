<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-6">
        <div class="flex items-center justify-center gap-2">
            <svg data-lucide="credit-card" class="w-7 h-7" style="color: #FCBF49;"></svg>
            <span class="text-xl font-semibold" style="color: #003049;">Card</span>
        </div>
    </div>

    <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3" style="background-color: #EAE2B7;">
            <svg data-lucide="mail" class="w-7 h-7" style="color: #003049;"></svg>
        </div>
        <h2 class="text-base font-semibold text-gray-800">Verifique seu e-mail</h2>
        <p class="text-sm text-gray-500 mt-1">
            Enviamos um link de confirmação para o seu e-mail.<br>
            Clique no link para ativar sua conta e ganhar <span class="font-semibold text-[#F77F00]">14 dias grátis do Pro</span>.
        </p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-center">
            Novo link enviado! Verifique sua caixa de entrada.
        </div>
    @endif

    <div class="space-y-3">
        <button wire:click="sendVerification"
                class="w-full py-2.5 rounded-lg text-sm font-semibold text-white transition hover:opacity-90 flex items-center justify-center gap-2"
                style="background-color: #003049;">
            <svg data-lucide="send" class="w-4 h-4"></svg>
            Reenviar e-mail de verificação
        </button>

        <button wire:click="logout" type="button"
                class="w-full py-2 rounded-lg text-sm text-gray-500 hover:text-gray-700 transition text-center">
            Sair da conta
        </button>
    </div>
</div>
