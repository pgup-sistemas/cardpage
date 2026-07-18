# Skill: admin
> Módulo M-09 · Admin SaaS · Filament 3
> Leia também: CLAUDE.md · docs/constitution.md · docs/design-system.md seção 6.4

---

## Contexto do módulo

Painel administrativo para a equipe PageUp. Acessível via `/admin`.
Stack: Filament 3 com visual customizado para a marca Card.
Recursos: usuários, cartões, planos, métricas, auditoria.

---

## Configuração do painel Filament

```php
// app/Providers/Filament/AdminPanelProvider.php
use Filament\Support\Colors\Color;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(['primary' => Color::hex('#003049')])
            ->brandName('Card · Admin')
            ->navigationGroups([
                NavigationGroup::make('Usuários e Cartões')->icon('heroicon-o-users'),
                NavigationGroup::make('Billing')->icon('heroicon-o-credit-card'),
                NavigationGroup::make('Sistema')->icon('heroicon-o-cog'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->authMiddleware([Authenticate::class])
            ->middleware([...])
            ->pages([Dashboard::class]);
    }
}
```

> **Nota:** Filament usa Heroicons internamente em suas macros. Isso é aceitável no painel Admin
> pois é uma área interna (PageUp), não voltada para usuários do SaaS.
> O design system com Lucide aplica-se ao painel do titular e ao cartão público.

---

## Guard de admin

```php
// Restringe /admin apenas a usuários com role 'admin'
// Usar spatie/laravel-permission

// Em User.php — já instalado via T-004
use Spatie\Permission\Traits\HasRoles;

// Seeder:
// $admin = User::where('email', 'admin@pageup.net.br')->first();
// $admin->assignRole('admin');
```

---

## UserResource

```php
// app/Filament/Resources/UserResource.php
class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Usuários';
    protected static ?string $navigationGroup = 'Usuários e Cartões';
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                BadgeColumn::make('plan')
                    ->label('Plano')
                    ->colors([
                        'warning' => 'free',
                        'success' => 'pro',
                    ]),
                TextColumn::make('trial_ends_at')
                    ->label('Trial até')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('plan_expires_at')
                    ->label('Pro até')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('card.slug')
                    ->label('Slug')
                    ->url(fn ($record) => $record->card ? url('/u/' . $record->card->slug) : null)
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('plan')
                    ->label('Plano')
                    ->options(['free' => 'Free', 'pro' => 'Pro']),
                Filter::make('trial_ativo')
                    ->label('Trial ativo')
                    ->query(fn ($query) => $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now())),
            ])
            ->actions([
                Action::make('impersonar')
                    ->label('Acessar como usuário')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (User $record, ActionGroup $group) {
                        // Log de auditoria obrigatório
                        AuditLog::create([
                            'action'       => 'impersonation',
                            'admin_id'     => auth()->id(),
                            'target_id'    => $record->id,
                            'ip_address'   => request()->ip(),
                        ]);
                        Auth::loginUsingId($record->id);
                        return redirect('/dashboard');
                    }),
                Action::make('suspender_cartao')
                    ->label('Suspender cartão')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->card?->is_active)
                    ->action(fn (User $record) => $record->card?->update(['is_active' => false])),
                Action::make('reativar_cartao')
                    ->label('Reativar cartão')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record) => !($record->card?->is_active))
                    ->action(fn (User $record) => $record->card?->update(['is_active' => true])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label('Nome')->required(),
            TextInput::make('email')->label('E-mail')->email()->required(),
            Select::make('plan')->label('Plano')->options(['free' => 'Free', 'pro' => 'Pro']),
            DateTimePicker::make('plan_expires_at')->label('Pro expira em'),
            DateTimePicker::make('trial_ends_at')->label('Trial expira em'),
        ]);
    }
}
```

---

## CardResource

```php
// app/Filament/Resources/CardResource.php
class CardResource extends Resource
{
    protected static ?string $model = Card::class;
    protected static ?string $navigationLabel = 'Cartões';
    protected static ?string $navigationGroup = 'Usuários e Cartões';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Titular')->searchable(),
                TextColumn::make('slug')->label('Slug')
                    ->url(fn ($record) => url('/u/' . $record->slug))
                    ->openUrlInNewTab(),
                TextColumn::make('display_name')->label('Nome no cartão'),
                IconColumn::make('is_active')->label('Ativo')->boolean(),
                TextColumn::make('views_count')
                    ->label('Visualizações')
                    ->counts('views')
                    ->sortable(),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Ativo'),
            ])
            ->actions([
                Action::make('suspender')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn ($record) => $record->is_active)
                    ->action(fn ($record) => $record->update(['is_active' => false])),
                Action::make('reativar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_active)
                    ->action(fn ($record) => $record->update(['is_active' => true])),
                ViewAction::make(),
            ]);
    }
}
```

---

## Dashboard Filament com Widgets de Métricas

```php
// app/Filament/Widgets/SaasStatsOverview.php
class SaasStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalUsers  = User::count();
        $proUsers    = User::where('plan', 'pro')
                          ->where('plan_expires_at', '>', now())->count();
        $trialUsers  = User::whereNotNull('trial_ends_at')
                          ->where('trial_ends_at', '>', now())->count();
        $totalCards  = Card::count();
        $activeCards = Card::where('is_active', true)->count();
        $mrr         = $proUsers * 19.90; // Valor fixo mensal

        $conversion = $totalUsers > 0
            ? round(($proUsers / $totalUsers) * 100, 1)
            : 0;

        return [
            Stat::make('Total de usuários', number_format($totalUsers))
                ->description("+{$trialUsers} em trial")
                ->color('primary'),
            Stat::make('Usuários Pro', number_format($proUsers))
                ->description("Conversão: {$conversion}%")
                ->color('success'),
            Stat::make('MRR', 'R$ ' . number_format($mrr, 2, ',', '.'))
                ->description('Receita mensal recorrente')
                ->color('warning'),
            Stat::make('Cartões ativos', number_format($activeCards) . ' / ' . number_format($totalCards))
                ->color('info'),
        ];
    }
}
```

---

## AuditLog — tabela de auditoria

```php
// database/migrations/create_audit_logs_table.php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->string('action');             // impersonation, plan_change, etc.
    $table->foreignId('admin_id')->constrained('users');
    $table->unsignedBigInteger('target_id')->nullable(); // user afetado
    $table->string('ip_address', 45)->nullable();
    $table->json('payload')->nullable();
    $table->timestamps();
});
```

---

## Checklist de entrega (T-080 a T-085)

- [ ] Filament instalado e `/admin` acessível
- [ ] Cores do painel customizadas para `#003049`
- [ ] `UserResource` com busca, filtros, badge de plano, actions de suspensão e impersonação
- [ ] `CardResource` com listagem e ações de ativar/suspender
- [ ] `SaasStatsOverview` widget com métricas (MRR, usuários, conversão)
- [ ] `AuditLog` registrado na impersonação
- [ ] Guard de admin: apenas usuários com role `admin` acessam `/admin`
- [ ] Teste: acessar `/admin` com e sem role → verificar bloqueio
