# Skill: auth
> Módulo M-01 · Autenticação e Conta
> Leia também: CLAUDE.md · docs/constitution.md · docs/design-system.md

---

## Contexto do módulo

Usa Laravel Breeze (Blade stack). **Não modifique** os controllers Breeze —
apenas customize as views e adicione lógica de negócio em observers/listeners.

Stack de auth: Breeze + Livewire 3 (stack `livewire`)

---

## Instalação

```bash
composer require laravel/breeze
php artisan breeze:install livewire
npm install && npm run build
php artisan migrate
```

---

## Customizações obrigatórias nas views Breeze

Todas as views ficam em `resources/views/auth/`.
Aplicar o layout `guest.blade.php` com o design system do Card.

### Layout guest.blade.php

```blade
{{-- resources/views/layouts/guest.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Card') · Card</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F0F0EE] min-h-screen flex items-center justify-center px-4 py-8 font-['Inter']">
  <div class="w-full max-w-[400px] bg-white rounded-[14px] shadow-[0_4px_24px_rgba(0,0,0,.10)] p-8">
    <!-- Logo -->
    <div class="flex items-center justify-center gap-2 mb-6">
      <i data-lucide="credit-card" class="w-7 h-7 text-[#FCBF49]"></i>
      <span class="text-[20px] font-semibold text-[#003049]">Card</span>
    </div>
    {{ $slot }}
  </div>
  <script>lucide.createIcons();</script>
</body>
</html>
```

### Tela de cadastro (`register.blade.php`)

Campos obrigatórios (além do Breeze padrão):
- `name` — nome completo (gera o slug)
- `slug` — gerado automaticamente em JS a partir do nome, editável, validado único

```blade
<!-- Campo slug — adicionar após o campo name -->
<div class="flex flex-col gap-1 mt-3">
  <label for="slug" class="text-[11px] font-medium text-[#666]">
    Seu link personalizado
  </label>
  <div class="flex items-center border border-[#ccc] rounded-[8px] overflow-hidden bg-[#FAFAF9]">
    <span class="px-3 py-[8px] text-[12px] text-[#aaa] bg-[#F0F0EE] border-r border-[#ccc] flex-shrink-0 select-none">
      card.app/u/
    </span>
    <input id="slug" name="slug" type="text"
           class="flex-1 px-[10px] py-[8px] text-[13px] text-[#222] bg-[#FAFAF9]
                  focus:outline-none placeholder:text-[#aaa]"
           placeholder="seu-nome" required>
  </div>
  <p class="text-[11px] text-[#888]">Apenas letras, números e hifens. Não pode ser alterado após 30 dias.</p>
  @error('slug')<p class="text-[11px] text-[#D62828]">{{ $message }}</p>@enderror
</div>
```

### Trial automático ao verificar e-mail

```php
// app/Listeners/ActivateTrialOnVerification.php
class ActivateTrialOnVerification
{
    public function handle(Verified $event): void
    {
        app(PlanService::class)->activateTrial($event->user);
    }
}

// app/Providers/EventServiceProvider.php
Verified::class => [ActivateTrialOnVerification::class],
```

### Card criado automaticamente após verificação

```php
// app/Observers/UserObserver.php
public function updated(User $user): void
{
    if ($user->wasChanged('email_verified_at') && $user->email_verified_at) {
        Card::firstOrCreate(
            ['user_id' => $user->id],
            [
                'slug'         => $user->slug,
                'display_name' => $user->name,
                'is_active'    => true,
                'show_watermark' => true,
            ]
        );
    }
}
```

---

## Migration de campos de plano em users

```php
// database/migrations/0002_add_plan_fields_to_users.php
Schema::table('users', function (Blueprint $table) {
    $table->string('slug')->unique()->after('name');
    $table->enum('plan', ['free', 'pro'])->default('free')->after('email');
    $table->timestamp('plan_expires_at')->nullable()->after('plan');
    $table->timestamp('trial_ends_at')->nullable()->after('plan_expires_at');
    $table->string('efi_subscription_id')->nullable()->after('trial_ends_at');
});
```

---

## Validação do slug

```php
// app/Http/Requests/Auth/RegisteredUserRequest.php
'slug' => [
    'required',
    'string',
    'min:3',
    'max:30',
    'regex:/^[a-z0-9\-]+$/',
    'unique:users,slug',
    'not_in:admin,api,login,register,dashboard,u,webhook',
],
```

---

## Exclusão de conta (LGPD Art. 18)

```php
// app/Http/Controllers/Settings/AccountDeletionController.php
public function destroy(Request $request): RedirectResponse
{
    $request->validate(['password' => ['required', 'current_password']]);

    $user = $request->user();

    // Apagar dados do cartão em cascata (FK cascadeOnDelete)
    // Apagar storage de imagens
    Storage::deleteDirectory("cards/{$user->id}");

    Auth::logout();
    $user->delete();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/')->with('mensagem', 'Sua conta foi excluída com sucesso.');
}
```

View de confirmação: campo de senha obrigatório + aviso de irreversibilidade.

---

## Rota de settings da conta

```php
Route::middleware(['auth'])->prefix('dashboard/settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');
    Route::delete('/account', [AccountDeletionController::class, 'destroy'])->name('account.destroy');
});
```

---

## Checklist de entrega (T-011 a T-017)

- [ ] Views Breeze customizadas com design system do Card (Inter, Lucide, cores)
- [ ] Campo `slug` no cadastro — gerado automaticamente do nome via JS
- [ ] Validação de slug: regex, unique, reserved words
- [ ] Listener `ActivateTrialOnVerification` dispara em `Verified`
- [ ] Observer cria `Card` automático após verificação de e-mail
- [ ] Middleware `CheckPlan` registrado e funcional
- [ ] Rota de exclusão de conta com apagamento de storage
- [ ] Teste Feature: cadastro → e-mail verificado → trial ativo → cartão criado

---

## Armadilhas comuns

- O Breeze Livewire usa `resources/js/app.js` com Alpine já incluído — não adicione Alpine de novo
- Slug deve ser convertido para lowercase + substituir espaços por hifens no JS antes de submeter
- Nunca permita slug com palavras reservadas (admin, api, webhook, etc.)
- O trial de 14 dias começa na verificação do e-mail, não no cadastro
- `cascadeOnDelete` nas FK cuida dos dados filhos — mas o storage deve ser apagado manualmente
