# Card — Arquitetura Técnica v1.0
> Padrões, estrutura e decisões de implementação · 2026-07-09

---

## 1. Estrutura de pastas completa

```
card/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                    ← Breeze (não modificar)
│   │   │   ├── CardController.php       ← cartão público /u/{slug}
│   │   │   ├── AppointmentController.php← agendamento público
│   │   │   └── ContactController.php    ← formulário de contato
│   │   ├── Middleware/
│   │   │   └── CheckPlan.php            ← bloqueia features Pro no Free
│   │   └── Requests/
│   │       ├── StoreCardRequest.php
│   │       ├── UpdateCardRequest.php
│   │       ├── StoreAppointmentRequest.php
│   │       └── StoreContactMessageRequest.php
│   ├── Livewire/
│   │   ├── Card/
│   │   │   ├── CardEditor.php           ← editor principal (painel)
│   │   │   ├── ColorPicker.php          ← 2 pickers com preview ao vivo
│   │   │   ├── CardPreview.php          ← iframe/componente preview
│   │   │   ├── LinkManager.php          ← CRUD + drag-and-drop
│   │   │   ├── PhotoGallery.php         ← upload e galeria
│   │   │   └── ContactEditor.php        ← contatos do cartão
│   │   ├── Schedule/
│   │   │   ├── ScheduleConfig.php       ← configurar disponibilidade
│   │   │   ├── AppointmentCalendar.php  ← calendário público (visitante)
│   │   │   └── AppointmentList.php      ← lista de agendamentos (painel)
│   │   └── Dashboard/
│   │       └── Overview.php             ← métricas do painel
│   ├── Models/
│   │   ├── User.php
│   │   ├── Card.php
│   │   ├── CardLink.php
│   │   ├── CardPhoto.php
│   │   ├── ContactMessage.php
│   │   ├── CardView.php
│   │   ├── CardSchedule.php
│   │   ├── CardScheduleSlot.php
│   │   └── CardAppointment.php
│   ├── Services/
│   │   ├── PlanService.php
│   │   ├── EfiBankService.php
│   │   ├── QrCodeService.php
│   │   ├── VCardService.php
│   │   ├── ImageService.php
│   │   └── AppointmentService.php
│   ├── Jobs/
│   │   ├── SendAppointmentNotification.php
│   │   ├── SendContactMessage.php
│   │   └── GenerateThumbnail.php
│   ├── Notifications/
│   │   ├── AppointmentRequested.php
│   │   ├── AppointmentConfirmed.php
│   │   └── AppointmentRefused.php
│   └── Filament/
│       └── Resources/
│           ├── UserResource.php
│           ├── CardResource.php
│           └── PlanResource.php
├── database/
│   ├── migrations/
│   │   ├── 0001_create_users_table.php
│   │   ├── 0002_add_plan_fields_to_users.php
│   │   ├── 0003_create_cards_table.php
│   │   ├── 0004_create_card_links_table.php
│   │   ├── 0005_create_card_photos_table.php
│   │   ├── 0006_create_contact_messages_table.php
│   │   ├── 0007_create_card_views_table.php
│   │   ├── 0008_create_card_schedules_table.php
│   │   ├── 0009_create_card_schedule_slots_table.php
│   │   └── 0010_create_card_appointments_table.php
│   └── seeders/
│       └── PlanSeeder.php               ← seed de planos Free/Pro
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php            ← painel autenticado
│   │   │   ├── guest.blade.php          ← páginas públicas
│   │   │   └── card.blade.php           ← cartão público (cores dinâmicas)
│   │   ├── card/
│   │   │   └── show.blade.php           ← template cartão público
│   │   ├── livewire/
│   │   │   ├── card/
│   │   │   ├── schedule/
│   │   │   └── dashboard/
│   │   └── emails/
│   │       ├── appointment-requested.blade.php
│   │       ├── appointment-confirmed.blade.php
│   │       └── appointment-refused.blade.php
│   └── css/
│       └── app.css                      ← Tailwind + variáveis CSS
├── routes/
│   ├── web.php                          ← todas as rotas web
│   └── api.php                          ← slots de agenda (JSON)
├── .claude/
│   └── skills/                          ← skills por módulo
├── docs/
│   ├── constitution.md
│   ├── requisitos.md
│   ├── prd.md
│   ├── arquitetura.md                   ← este arquivo
│   └── tasks.md
└── CLAUDE.md                            ← entry point Claude Code
```

---

## 2. Migrations detalhadas

### users (extensão do Breeze)
```php
// Adicionado via migration separada após Breeze
Schema::table('users', function (Blueprint $table) {
    $table->enum('plan', ['free', 'pro'])->default('free')->after('email');
    $table->timestamp('plan_expires_at')->nullable()->after('plan');
    $table->timestamp('trial_ends_at')->nullable()->after('plan_expires_at');
    $table->string('efi_subscription_id')->nullable()->after('trial_ends_at');
});
```

### cards
```php
Schema::create('cards', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('slug')->unique();
    $table->boolean('is_active')->default(true);
    $table->string('display_name');
    $table->string('title')->nullable();
    $table->string('company')->nullable();
    $table->text('bio')->nullable();
    $table->string('profile_photo')->nullable();
    $table->string('cover_photo')->nullable();
    $table->string('logo')->nullable();
    $table->char('brand_color_primary', 7)->nullable(); // #RRGGBB
    $table->char('brand_color_button', 7)->nullable();  // #RRGGBB
    $table->boolean('show_watermark')->default(true);
    $table->string('contact_email')->nullable();
    $table->string('contact_phone')->nullable();
    $table->string('address')->nullable();
    $table->string('website')->nullable();
    $table->timestamps();
});
```

### card_links
```php
Schema::create('card_links', function (Blueprint $table) {
    $table->id();
    $table->foreignId('card_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['social', 'custom', 'pix', 'schedule']);
    $table->string('label');
    $table->string('url');
    $table->string('icon')->nullable(); // nome do ícone Lucide
    $table->boolean('is_active')->default(true);
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();
});
```

### card_appointments
```php
Schema::create('card_appointments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('card_id')->constrained()->cascadeOnDelete();
    $table->foreignId('card_schedule_id')->constrained()->cascadeOnDelete();
    $table->date('appointment_date');
    $table->time('appointment_time');
    $table->string('visitor_name');
    $table->string('visitor_email');
    $table->string('visitor_phone')->nullable();
    $table->text('notes')->nullable();
    $table->enum('status', ['pending', 'confirmed', 'refused', 'cancelled'])
          ->default('pending');
    $table->string('confirmation_token')->unique();
    $table->timestamp('confirmed_at')->nullable();
    $table->timestamp('refused_at')->nullable();
    $table->timestamps();

    $table->index(['card_id', 'appointment_date', 'status']);
});
```

---

## 3. Models — padrões

### Card.php
```php
class Card extends Model
{
    protected $fillable = [
        'user_id', 'slug', 'is_active', 'display_name', 'title',
        'company', 'bio', 'profile_photo', 'cover_photo', 'logo',
        'brand_color_primary', 'brand_color_button', 'show_watermark',
        'contact_email', 'contact_phone', 'address', 'website',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'show_watermark' => 'boolean',
    ];

    // Retorna cor primária respeitando o plano
    public function getPrimaryColorAttribute(): string
    {
        return ($this->user->plan === 'pro' && $this->brand_color_primary)
            ? $this->brand_color_primary
            : '#003049';
    }

    // Retorna cor de botão respeitando o plano
    public function getButtonColorAttribute(): string
    {
        return ($this->user->plan === 'pro' && $this->brand_color_button)
            ? $this->brand_color_button
            : '#D62828';
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function links(): HasMany { return $this->hasMany(CardLink::class)->orderBy('sort_order'); }
    public function photos(): HasMany { return $this->hasMany(CardPhoto::class)->orderBy('sort_order'); }
    public function schedule(): HasOne { return $this->hasOne(CardSchedule::class); }
    public function appointments(): HasMany { return $this->hasMany(CardAppointment::class); }
    public function messages(): HasMany { return $this->hasMany(ContactMessage::class); }
    public function views(): HasMany { return $this->hasMany(CardView::class); }
}
```

---

## 4. Services — responsabilidades

### PlanService
```php
// Verifica se usuário tem acesso a feature Pro
public function can(User $user, string $feature): bool

// Verifica limite de quantidade (ex: links, fotos)
public function withinLimit(User $user, string $resource): bool

// Ativa trial de 14 dias
public function activateTrial(User $user): void

// Faz downgrade para Free
public function downgrade(User $user): void
```

### AppointmentService
```php
// Retorna slots disponíveis para uma data
public function availableSlots(CardSchedule $schedule, Carbon $date): array

// Verifica se slot está disponível (não bloqueado por confirmação)
public function isSlotAvailable(CardSchedule $schedule, Carbon $date, string $time): bool

// Cria solicitação de agendamento com token único
public function createRequest(Card $card, array $data): CardAppointment

// Confirma agendamento e bloqueia slot
public function confirm(CardAppointment $appointment): void

// Recusa e libera slot
public function refuse(CardAppointment $appointment): void
```

### ImageService
```php
// Salva foto com validação, redimensiona e gera thumbnail
public function saveProfilePhoto(UploadedFile $file, Card $card): string

// Salva foto de capa
public function saveCoverPhoto(UploadedFile $file, Card $card): string

// Gera thumbnail 300x300 de foto da galeria
public function generateThumbnail(string $path): string
```

---

## 5. Middleware CheckPlan

```php
class CheckPlan
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();
        $planService = app(PlanService::class);

        if (!$planService->can($user, $feature)) {
            if ($request->expectsJson()) {
                return response()->json(['erro' => 'Recurso disponível apenas no plano Pro.'], 403);
            }
            return redirect()->route('dashboard.plan')
                ->with('aviso', 'Este recurso requer o plano Pro.');
        }

        return $next($request);
    }
}

// Uso nas rotas:
Route::middleware(['auth', 'plan:agenda'])->group(function () {
    Route::get('/dashboard/schedule', [ScheduleController::class, 'index']);
});
```

---

## 6. Template do cartão público — cores dinâmicas

```blade
{{-- resources/views/layouts/card.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- SEO e Open Graph --}}
    <title>{{ $card->display_name }} · Card</title>
    <meta name="description" content="{{ Str::limit($card->bio, 160) }}">
    <meta property="og:title" content="{{ $card->display_name }}">
    <meta property="og:description" content="{{ Str::limit($card->bio, 160) }}">
    <meta property="og:image" content="{{ Storage::url($card->profile_photo) }}">
    <meta property="og:url" content="{{ url('/u/' . $card->slug) }}">

    {{-- Cores dinâmicas do titular --}}
    <style>
        :root {
            --card-primary: {{ $card->primary_color }};
            --card-button:  {{ $card->button_color }};
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex items-start justify-center py-8 px-4">
    <div class="w-full max-w-[400px]">
        @yield('content')
    </div>
</body>
</html>
```

---

## 7. Rotas completas (web.php)

```php
// Público — sem auth
Route::get('/u/{slug}', [CardController::class, 'show'])->name('card.show');
Route::post('/u/{slug}/contact', [ContactController::class, 'store'])->name('card.contact');
Route::get('/u/{slug}/vcf', [CardController::class, 'downloadVcf'])->name('card.vcf');
Route::get('/u/{slug}/agendar', [AppointmentController::class, 'create'])->name('card.schedule');
Route::post('/u/{slug}/agendar', [AppointmentController::class, 'store'])->name('card.schedule.store');
Route::get('/u/{slug}/agendar/slots', [AppointmentController::class, 'slots'])->name('card.schedule.slots');
Route::get('/appointments/{token}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
Route::get('/appointments/{token}/refuse', [AppointmentController::class, 'refuse'])->name('appointments.refuse');

// Painel — autenticado
Route::middleware(['auth', 'verified'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/card', [CardEditorController::class, 'index'])->name('card');
    Route::get('/links', [LinkManagerController::class, 'index'])->name('links');
    Route::get('/photos', [PhotoGalleryController::class, 'index'])->name('photos');
    Route::get('/contacts', [ContactEditorController::class, 'index'])->name('contacts');
    Route::get('/messages', [MessageController::class, 'index'])->name('messages')->middleware('plan:messages');
    Route::get('/plan', [PlanController::class, 'index'])->name('plan');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

    // Agenda — Pro only
    Route::middleware('plan:agenda')->group(function () {
        Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments');
    });
});

// Webhooks Efi Bank — sem CSRF
Route::post('/webhook/efibank', [EfiBankWebhookController::class, 'handle'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
```

---

## 8. Detecção automática de rede social por URL

```php
// app/Services/SocialLinkService.php
public function detectIcon(string $url): string
{
    return match(true) {
        str_contains($url, 'instagram.com')  => 'instagram',
        str_contains($url, 'linkedin.com')   => 'linkedin',
        str_contains($url, 'youtube.com')    => 'youtube',
        str_contains($url, 'tiktok.com')     => 'music',       // Lucide: music
        str_contains($url, 'twitter.com')    => 'twitter',
        str_contains($url, 'x.com')          => 'twitter',
        str_contains($url, 'facebook.com')   => 'facebook',
        str_contains($url, 'telegram.me')    => 'send',
        str_contains($url, 't.me')           => 'send',
        str_contains($url, 'pinterest.com')  => 'image',
        str_contains($url, 'spotify.com')    => 'music-2',
        str_contains($url, 'wa.me')          => 'message-circle',
        str_contains($url, 'whatsapp.com')   => 'message-circle',
        default                              => 'link',
    };
}
```

---

## 9. Configuração MCP (`.claude/mcp.json`)

```json
{
  "mcpServers": {
    "filesystem": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-filesystem", "."],
      "description": "Acesso ao filesystem do projeto Laravel"
    },
    "github": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-github"],
      "env": { "GITHUB_TOKEN": "${GITHUB_TOKEN}" },
      "description": "Commits, branches e PRs via GitHub API"
    },
    "playwright": {
      "command": "npx",
      "args": ["-y", "@playwright/mcp"],
      "description": "Validar UI do cartão público e painel no browser"
    },
    "sequential-thinking": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-sequential-thinking"],
      "description": "Raciocínio em cadeia para tarefas longas multi-etapa"
    }
  }
}
```

**Por que cada um:**
- `filesystem` — lê e escreve arquivos do Laravel sem copiar/colar
- `github` — commits atômicos por módulo com mensagem Conventional Commits
- `playwright` — abre o browser e valida o cartão público após gerar código
- `sequential-thinking` — quebra tarefas complexas (ex: agenda inteira) em passos antes de executar

---

*Arquitetura v1.0 · Card SaaS · PageUp Sistemas · 2026*
