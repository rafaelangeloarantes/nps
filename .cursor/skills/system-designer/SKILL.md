# Skill: System Designer — Template base para novos projetos

Use esta skill quando o usuário pedir para **inicializar um projeto com System Designer**, **criar um novo projeto com a base System Designer** ou **usar o template System Designer**. Garante que todo projeto nasça com a mesma estrutura, componentes e regras testadas.

## O que é o System Designer

- **Index único:** toda a aplicação entra por `index.php`. Menu, topo, CSS e JS centralizados; conteúdo de cada tela é `include` em `paginas/` via `?p=`.
- **Roteamento:** `index.php?p=dashboard|formularios|listagem|configuracoes|...` com whitelist; qualquer outro valor cai em dashboard.
- **Componentes padrão:** Loading (overlay bloqueante), Modal, Toast, Alert inline, formulários com validação e repopulação.
- **Segurança:** CSRF em formulários, `session_start` único, escape de saída (`htmlspecialchars`), .htaccess bloqueando `cache/` e `data/`.
- **Front:** HTML5 semântico, CSS em arquivos separados (design tokens, dark mode), jQuery, sem frameworks JS modernos.

## Estrutura de pastas (criar ou copiar de .cursor/template)

```
/projeto
├── .cursor/
│   └── template/          # Seed: copiar conteúdo para a raiz ao inicializar
├── api/                   # Endpoints JSON (ex.: localidade.php)
├── cache/                 # Cache em arquivo (ex.: localidade_data.cache)
├── css/
│   ├── style.css         # Design system, layout, sidebar, topbar, alertas, toast
│   ├── form.css          # Formulários, seções, Select2, máscaras
│   ├── modal.css
│   ├── loading.css
│   ├── datatable-override.css
│   └── charts.css
├── data/                  # JSON estáticos (ex.: all.json para localidade)
├── js/
│   ├── datatable-config.js  # NpsDataTable.create() — padrão de listagens
│   ├── main.js           # Sidebar, dark mode, toast, alert
│   ├── form.js           # Select2, máscaras, moeda BRL, localidade
│   ├── loading.js        # TemplateLoading.show/hide, ajaxStart/Stop, form submit
│   ├── modal.js          # TemplateModal.open/close
│   └── charts.js         # Chart.js (se usar)
├── paginas/               # Fragmentos PHP incluídos no index (apenas conteúdo do <main>)
│   ├── dashboard.php
│   ├── formularios.php
│   ├── listagem.php
│   └── configuracoes.php
├── .htaccess
├── index.php              # Único ponto de entrada: shell + roteamento ?p=
├── logout.php
├── processar-form.php     # Exemplo; em produção um por fluxo ou módulo
└── formularios.php        # Redirecionamento para index.php?p=formularios
```

## Como inicializar um novo projeto com System Designer

1. **Se existir `.cursor/template/`** no projeto:
   - Copiar **todo o conteúdo** de `.cursor/template/` para a **raiz** do projeto (não sobrescrever `.cursor/`).
   - Substituir no código o nome "System Designer" ou "Modelo" pelo nome do projeto, quando fizer sentido.
   - Garantir pastas `cache/` e `data/` com permissão de escrita/leitura conforme necessário.

2. **Se NÃO existir `.cursor/template/`** (ex.: projeto novo só com .cursor):
   - Criar a estrutura de pastas acima.
   - Criar os arquivos conforme as convenções abaixo e os exemplos da skill (index único, CSRF, Loading, Modal, Toast, Alert, paginas com `$pageTitle` e buffer no index).

## Convenções obrigatórias

- **index.php:** `session_start()` no topo; whitelist `$paginasPermitidas`; CSRF token em sessão; `ob_start` + `require paginas/$pagina.php` + `ob_get_clean()`; exibir `$mainContent` no `<main>`; cada fragmento em `paginas/` deve definir `$pageTitle` no início.
- **Formulários:** campo oculto `csrf_token`; processar-form valida com `hash_equals`; após sucesso, regenerar token e redirecionar para `index.php?p=...`.
- **Inclusão de páginas:** sempre `require __DIR__ . '/paginas/' . $pagina . '.php'` com `$pagina` vindo da whitelist (nunca direto de `$_GET` sem validação).
- **Saída ao usuário:** sempre `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` em dados dinâmicos em HTML.
- **Assets locais (CSS/JS):** `asset()` (URL relativa) ou `asset_url()` (absoluta) — versionamento automático via `filemtime`.
- **JS:** toast e alert usam `.text()` (ou equivalente) para mensagens dinâmicas; modal com body/footer string deve escapar (já feito em modal.js).
- **Loading:** todo submit de formulário e toda requisição AJAX disparam o overlay; usar `TemplateLoading.show()`/`hide()` para operações manuais.

## Componentes que todo projeto pode usar

| Componente   | Uso |
|-------------|-----|
| **Loading** | Inclusão de `css/loading.css` e `js/loading.js` (após jQuery). Automático em forms e AJAX. |
| **Modal**   | `css/modal.css`, `js/modal.js`. `data-modal-open="id"`, `data-modal-close`, ou `TemplateModal.open({ title, body, footer })`. |
| **Toast**   | `showToast(type, message, duration)` — feedback rápido no canto da tela. |
| **Alert**   | `showAlert(type, message, containerId)` — mensagem inline em `#alertContainer`. |
| **DataTable** | `js/datatable-config.js` → `NpsDataTable.create()`. HTML: `table-wrapper dt-wrapper`. Ver skill `datatable`. |
| **Formulário** | Padrão: card com form, validação back-end, sessão para erros/sucesso/repopulação, CSRF. |

## Checklist pós-inicialização

- [ ] `index.php` com roteamento `?p=` e whitelist
- [ ] `paginas/*.php` definem `$pageTitle` e só contêm conteúdo do `<main>`
- [ ] Formulários com `csrf_token` e processamento com validação CSRF
- [ ] `logout.php` destruindo sessão e redirecionando
- [ ] `.htaccess` com UTF-8 e bloqueio de `cache/` e `data/`
- [ ] CSS/JS sem inline; Loading e Modal incluídos onde houver form ou AJAX
- [ ] Listagens com `NpsDataTable.create()` e skill `datatable` aplicada
- [ ] Foco em acessibilidade: `:focus-visible`, `aria-label`, `role` onde aplicável

## Quando usar esta skill

- Comando "Inicializar System Designer" ou "Criar novo projeto com System Designer".
- Comando "novo-projeto" quando o usuário escolher base System Designer.
- Pedidos como "use o template que definimos" ou "comece com a mesma base do System Designer".
