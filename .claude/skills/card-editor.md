# Skill: card-editor
> Módulo M-02 · Cartão Digital — Editor e Cartão Público
> Leia também: CLAUDE.md · docs/constitution.md seções 2.8 e 2.10 · docs/arquitetura.md seções 3 e 6

---

## Contexto do módulo

O cartão é o produto central do SaaS. Tem duas faces:
1. **Cartão público** `/u/{slug}` — acessível sem login, mobile-first, com cores dinâmicas
2. **Editor no painel** `/dashboard/card` — Livewire com preview ao vivo

---

## Header com foto de capa (constitution.md 2.8)

```
┌─────────────────────────────────────┐
│  [cover_photo — 100% largura]       │  height: 110px
│  overlay: rgba(0,0,0,0.15)          │
└──────────────────────────────────────┘
  [avatar 56px: borda inferior da capa, border: 3px solid #fff]
  Nome · Cargo · Badge Pro (se aplicável)
  [ícones redes sociais — Lucide, cor EAE2B7 sobre fundo primário]
```

Fallback de capa: `background-color: var(--card-primary)`.

---

## Cores dinâmicas (constitution.md 2.10)

```blade
{{-- Em layouts/card.blade.php --}}
<style>
  :root {
    --card-primary: {{ $card->primary_color }};  {{-- accessor do Model --}}
    --card-button:  {{ $card->button_color }};   {{-- accessor do Model --}}
  }
</style>
```

Accessors no Model `Card`:
- `getPrimaryColorAttribute()` → retorna `brand_color_primary` se Pro, senão `#003049`
- `getButtonColorAttribute()` → retorna `brand_color_button` se Pro, senão `#D62828`

Pickers são **feature Pro** — renderizar condicionalmente com `@if(auth()->user()->plan === 'pro')`.

---

## Componentes Livewire do editor

### CardEditor
- Campos: display_name, title, company, bio (textarea)
- Upload cover_photo com preview imediato
- Upload profile_photo com Cropper.js
- Emite evento `card-updated` para CardPreview re-renderizar

### ColorPicker (Pro)
- Dois `<input type="color">` independentes
- Wire:model em `brand_color_primary` e `brand_color_button`
- Preview ao vivo via `wire:model.live`
- Valida HEX no backend (regex `/^#[0-9A-Fa-f]{6}$/`)

### CardPreview
- Renderiza o cartão em iframe ou componente Blade
- Ouve evento `card-updated` e re-renderiza

---

## Checklist de entrega (T-018 a T-041)

- [ ] Migration cards criada e rodada
- [ ] Model Card com accessors de cor funcionando
- [ ] Rota /u/{slug} retornando 404 para cartão inativo
- [ ] Header com foto de capa exibido corretamente no mobile
- [ ] Avatar sobreposto à capa com borda branca
- [ ] Todos os blocos do cartão renderizando
- [ ] Open Graph correto ao compartilhar no WhatsApp
- [ ] Editor Livewire salvando e refletindo no preview
- [ ] ColorPicker (Pro) bloqueado no Free com mensagem de upgrade
- [ ] Watermark visível no Free, oculta no Pro

