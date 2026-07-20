# Card — PRD (Product Requirements Document) v1.2
> Histórias de usuário priorizadas · PageUp Sistemas · 2026-07-09 · Atualizado: 2026-07-20

---

## Épicos e Histórias de Usuário — MVP v1.0

Prioridade: P1 (bloqueante) → P2 (essencial) → P3 (desejável no MVP)

---

### Épico 1 · Conta e Autenticação

| ID | História | Critério de aceite | Prio |
|---|---|---|---|
| U-01 | Como visitante, quero criar uma conta com e-mail e senha para ter meu cartão | Conta criada, e-mail de verificação enviado, redirecionado ao painel | P1 |
| U-02 | Como usuário, quero fazer login e permanecer autenticado | Login funciona, "lembrar-me" persiste sessão por 30 dias | P1 |
| U-03 | Como usuário, quero recuperar minha senha por e-mail | Link de reset enviado, senha alterada com sucesso | P1 |
| U-04 | Como usuário, quero excluir minha conta e todos os meus dados | Conta e dados apagados, confirmação exigida, e-mail de confirmação enviado | P2 |

---

### Épico 2 · Cartão Digital

| ID | História | Critério de aceite | Prio |
|---|---|---|---|
| C-01 | Como titular, quero fazer upload da minha foto de capa para personalizar o header | Foto padronizada no servidor (EXIF corrigido, crop 3:1 do centro, 1200×400px), exibida como background do header, fallback na cor primária | P1 |
| C-02 | Como titular, quero fazer upload da minha foto de perfil | Foto padronizada no servidor (EXIF corrigido, crop quadrado do topo, 400×400px), exibida sobreposta à capa | P1 |
| C-03 | Como titular, quero preencher nome, cargo e bio do meu cartão | Campos salvos e exibidos no cartão público | P1 |
| C-04 | Como titular Pro, quero escolher a cor primária do meu cartão | Color picker salva HEX válido, preview ao vivo no painel, aplicado no cartão público | P2 |
| C-05 | Como titular Pro, quero escolher a cor dos botões do meu cartão | Picker independente da cor primária, preview ao vivo | P2 |
| C-06 | Como titular Pro, quero fazer upload do meu logotipo | Logo exibido no cartão separado da foto de perfil | P2 |
| C-07 | Como titular, quero ativar/desativar meu cartão sem excluí-lo | Toggle no painel, cartão desativado exibe página de indisponibilidade | P2 |
| C-08 | Como visitante, quero ver o cartão com as cores e identidade do titular | Cartão público reflete brand_color_primary e brand_color_button do titular | P1 |

---

### Épico 3 · Links e Redes Sociais

| ID | História | Critério de aceite | Prio |
|---|---|---|---|
| L-01 | Como titular, quero adicionar links de redes sociais com ícone automático | Ícone Lucide correto detectado pela URL, botão exibido no cartão | P1 |
| L-02 | Como titular, quero adicionar links customizados com label livre | Até 5 no Free, ilimitado no Pro, exibidos no cartão | P1 |
| L-03 | Como titular, quero reordenar meus links arrastando | Drag-and-drop funciona, ordem persistida | P2 |
| L-04 | Como titular, quero ativar/desativar links individualmente | Toggle por link, desativado não aparece no cartão público | P2 |
| L-05 | Como titular, quero adicionar minha chave PIX ao cartão | Botão "Pagar via PIX" exibido com QR Code PIX gerado | P1 |

---

### Épico 4 · Contatos e vCard

| ID | História | Critério de aceite | Prio |
|---|---|---|---|
| CT-01 | Como titular, quero exibir telefone com botão de WhatsApp direto | Botão abre `wa.me/` com número formatado | P1 |
| CT-02 | Como titular, quero exibir e-mail e endereço com link Maps | mailto: e link Google Maps funcionando | P1 |
| CT-03 | Como visitante, quero baixar o contato do titular em .vcf | Arquivo vCard 3.0 com nome, telefone, e-mail, site e foto | P2 |

---

### Épico 5 · Formulário de Contato

| ID | História | Critério de aceite | Prio |
|---|---|---|---|
| F-01 | Como visitante, quero enviar mensagem ao titular pelo cartão | Formulário com nome, e-mail, telefone, mensagem — enviado via Resend | P1 |
| F-02 | Como titular, quero receber a mensagem no meu e-mail | E-mail Resend com dados do visitante entregue em até 30s | P1 |
| F-03 | Como titular Pro, quero ver histórico de mensagens no painel | Lista de mensagens com lidas/não lidas, data, dados do remetente | P3 |

---

### Épico 5b · Galeria e Mídia

| ID | História | Critério de aceite | Prio |
|---|---|---|---|
| G-01 | Como visitante, quero ver a galeria de fotos do titular em grade | Grid 3 colunas, fotos com proporção quadrada via object-cover | P1 |
| G-02 | Como visitante, quero clicar em uma foto e vê-la em tela cheia | Lightbox fullscreen com overlay escuro, foto centralizada, caption exibido | P1 |
| G-03 | Como visitante, quero navegar entre as fotos no lightbox | Setas prev/next, dots indicadores de posição, contador "X / Y", swipe touch, teclado ← → Esc | P2 |
| G-04 | Como titular, quero excluir fotos da galeria com um botão visível | Botão X vermelho sempre visível no canto superior direito de cada foto (inclusive no mobile) | P2 |

---

### Épico 6 · Compartilhamento e QR Code

| ID | História | Critério de aceite | Prio |
|---|---|---|---|
| S-01 | Como titular, quero gerar e baixar o QR Code do meu cartão | Download em PNG e SVG disponíveis no painel | P1 |
| S-02 | Como titular, quero copiar o link do meu cartão | Botão copia URL para clipboard com feedback visual | P1 |
| S-03 | Como qualquer pessoa, quero que o link do cartão tenha preview rico no WhatsApp | Open Graph com título, bio e foto de perfil | P2 |

---

### Épico 7 · Painel do Usuário

| ID | História | Critério de aceite | Prio |
|---|---|---|---|
| P-01 | Como titular, quero ver quantas vezes meu cartão foi acessado | Contadores de visualizações totais e dos últimos 7 dias no dashboard | P2 |
| P-01a | Como titular, quero ver um gráfico de visitas dos últimos 30 dias | Gráfico de barras diário, lacunas preenchidas com zero, tooltip com data e total | P2 |
| P-01b | Como titular, quero saber de onde vêm meus visitantes | Painel de origens: direto, WhatsApp, Instagram, Google, Facebook, LinkedIn, TikTok, Telegram, Outros — com percentual e cor por plataforma | P2 |
| P-01c | Como titular, quero ver quais links recebem mais cliques | Ranking com barra proporcional de clicks por link | P2 |
| P-02 | Como titular, quero editar meu cartão com preview ao vivo | Painel exibe preview atualizado em tempo real (Livewire) | P1 |
| P-03 | Como titular, quero gerenciar minha assinatura | Ver plano, vencimento, botão de upgrade | P1 |
| P-04 | Como titular, quero saber quantas mensagens não li sem entrar na página | Badge com contador na sidebar ao lado de "Mensagens" | P3 |

---

### Épico 8 · Billing e Planos

| ID | História | Critério de aceite | Prio |
|---|---|---|---|
| B-01 | Como visitante, quero iniciar trial de 14 dias do Pro sem cartão | Trial ativado no cadastro, data de expiração visível, downgrade automático | P1 |
| B-02 | Como titular Free, quero fazer upgrade para Pro | Fluxo de pagamento Efi Bank (PIX/boleto/cartão), plano ativado via webhook | P1 |
| B-03 | Como titular Pro, quero receber aviso antes do vencimento | E-mails 3 dias antes, no dia e 3 dias após o vencimento | P2 |
| B-04 | Como titular inadimplente, quero que meus dados sejam preservados no downgrade | Downgrade automático para Free, dados mantidos, features Pro desativadas | P1 |

---

### Épico 9 · Admin SaaS

| ID | História | Critério de aceite | Prio |
|---|---|---|---|
| ADM-01 | Como admin, quero listar e filtrar usuários | Filament table com busca por nome/e-mail, filtro por plano | P1 |
| ADM-02 | Como admin, quero suspender um cartão | Cartão suspenso exibe página de indisponibilidade | P2 |
| ADM-03 | Como admin, quero ver métricas do SaaS | MRR, usuários ativos, total de cartões, conversão Free→Pro | P2 |
| ADM-04 | Como admin, quero acessar o painel de um usuário | Impersonation com log de auditoria | P3 |

---

### Épico 10 · Agenda e Agendamentos (Pro)

| ID | História | Critério de aceite | Prio |
|---|---|---|---|
| AG-01 | Como titular Pro, quero configurar minha disponibilidade semanal | Definir dias, faixas de horário e duração do slot | P1 |
| AG-02 | Como titular Pro, quero ver solicitações de agendamento recebidas | Lista com status (pendente/confirmado/recusado), dados do visitante | P1 |
| AG-03 | Como titular Pro, quero confirmar ou recusar agendamentos | Ação no painel ou link direto do e-mail, slot bloqueado ao confirmar | P1 |
| AG-04 | Como visitante, quero ver o calendário de disponibilidade no cartão | Calendário visual com dias disponíveis, slots de horário selecionáveis | P1 |
| AG-05 | Como visitante, quero solicitar um horário preenchendo meus dados | Formulário nome/e-mail/telefone/observação, confirmação visual após envio | P1 |
| AG-06 | Como visitante, quero receber confirmação do agendamento por e-mail | E-mail com data, hora e dados de contato do titular | P1 |
| AG-07 | Como titular, quero receber notificação de nova solicitação por e-mail | E-mail com dados do visitante e links de confirmar/recusar | P1 |
| AG-08 | Como titular, quero que slots confirmados não apareçam para outros visitantes | Slots bloqueados ocultos ou marcados como indisponíveis | P1 |

---

## Histórias fora do MVP (backlog)

| ID | História | Fase |
|---|---|---|
| BK-01 | Como titular, quero ter múltiplos cartões na mesma conta | v1.1 |
| BK-02 | Como titular, quero ver analytics por período customizado com filtros | v1.1 |
| BK-03 | Como titular, quero que agendamentos sejam confirmados automaticamente | v1.1 |
| BK-04 | Como visitante, quero adicionar agendamento confirmado ao Google Calendar | v1.1 |
| BK-05 | Como titular, quero usar meu próprio domínio no cartão | v1.2 |
| BK-06 | Como titular, quero login via Google | v1.2 |
| BK-07 | Como titular, quero escolher entre temas visuais do marketplace | v2.0 |
| BK-08 | Como titular, quero página dedicada de analytics com mapa de calor das seções | v1.1 |
| BK-09 | Como titular, quero exportar leads (mensagens e agendamentos) em CSV | v1.1 |
| BK-10 | Como titular, quero receber notificação push no painel ao receber nova mensagem | v1.1 |

---

## Changelog

| Versão | Data | Alterações |
|---|---|---|
| v1.0 | 2026-07-09 | Documento inicial |
| v1.1 | 2026-07-09 | + Épico 2 atualizado com capa/cores · + Épico 10 Agenda completo |
| v1.2 | 2026-07-20 | + Épico 5b Galeria/Lightbox (G-01 a G-04) · + P-01a/b/c analytics · + P-04 badge mensagens · + C-01/C-02 padronização de imagens · + BK-08/09/10 backlog |

---

*PRD v1.0 · Card SaaS · PageUp Sistemas · 2026*
