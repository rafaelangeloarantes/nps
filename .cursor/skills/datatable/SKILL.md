# Skill: DataTable — Padrão visual e técnico

Use esta skill sempre que criar, ajustar ou revisar **listagens com DataTables** no painel admin (CRUD, logs, respostas etc.).

## Arquivos obrigatórios

| Arquivo | Função |
|---------|--------|
| `js/datatable-config.js` | Configuração central (`NpsDataTable.create`) |
| `css/datatable-override.css` | Tipografia, espaçamentos, toolbar/footer (carregar **por último**) |
| `css/style.css` | Layout base `.dt-toolbar`, `.dt-footer`, `.dt-wrapper` |

## Dependências no `index.php`

Ordem de CSS:
```html
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/datatable-override.css">
```

Ordem de JS (após jQuery e plugin DataTables):
```html
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="js/datatable-config.js"></script>
<script src="js/main.js"></script>
<!-- depois: js/admin-{modulo}.js -->
```

**Não usar** `dataTables.bootstrap5.min.css/js` — o visual é controlado pelo design system próprio.

## HTML da página (fragmento em `paginas/`)

```html
<div class="card">
    <div class="card-body">
        <div class="table-wrapper dt-wrapper">
            <table id="tabelaModulo" class="display" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Criado em</th>
                        <th class="dt-no-sort">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
```

Regras:
- Wrapper **sempre** com classes `table-wrapper dt-wrapper`
- Coluna de ações com `class="dt-no-sort"` no `<th>`
- Tabela com `class="display"` e `width="100%"`
- **Não** incluir `<tbody>` quando usar server-side AJAX

## Inicialização JavaScript

**Sempre** usar `NpsDataTable.create()` — **nunca** chamar `$('#tabela').DataTable()` direto.

```javascript
tabela = NpsDataTable.create('#tabelaModulo', {
    processing: true,
    serverSide: true,
    ajax: 'ajax/admin/modulo_listar.php',
    order: [[1, 'asc']],
    columns: [
        { data: 'id' },
        { data: 'nome' },
        { data: 'criado_em' },
        { data: 'acoes', orderable: false, render: function (id) {
            return '<button class="btn-icon btn-icon-sm btn-edit" data-id="' + id + '">' +
                   '<i class="bi bi-pencil"></i></button>';
        }}
    ]
});
```

### Defaults já aplicados por `NpsDataTable`

- `dom: '<"dt-toolbar"lf>rt<"dt-footer"ip>'` — toolbar e footer estilizados
- `stripeClasses: []` — sem listras
- `pageLength: 10`, `lengthMenu: [10, 25, 50, 100]`
- Idioma PT-BR via CDN HTTPS
- Ajuste de colunas em resize, toggle da sidebar e `ResizeObserver`

Só sobrescreva `dom`, `language` ou `stripeClasses` se houver motivo documentado.

## Proibições

- NUNCA inicializar DataTable sem `NpsDataTable.create()`
- NUNCA omitir `table-wrapper dt-wrapper` no HTML
- NUNCA usar URL de idioma com `//` (protocol-relative) — usar `https://`
- NUNCA colocar CSS ou JS inline para estilizar tabela
- NUNCA duplicar defaults de toolbar/footer em cada módulo

## Server-side (listagem AJAX)

Endpoint deve retornar:
```json
{
  "draw": 1,
  "recordsTotal": 100,
  "recordsFiltered": 50,
  "data": []
}
```

Parâmetros recebidos: `draw`, `start`, `length`, `search[value]`, `order[0][column]`, `order[0][dir]`.

## UX visual (referência)

- Fonte: `Inter` via `--font`
- Cabeçalho: uppercase, `.6875rem`, letter-spacing `.06em`, padding generoso
- Células: `.875rem`, line-height ~1.55, hover com `--surface-2`
- Estado vazio: mensagem centralizada com padding amplo
- Card: `.card-body` sem padding quando contém só a tabela (CSS automático)
- Paginação: botões arredondados, página ativa em cinza neutro

## Checklist ao criar nova listagem

- [ ] HTML com `table-wrapper dt-wrapper` e `dt-no-sort` em Ações
- [ ] JS usa `NpsDataTable.create('#id', { ... })`
- [ ] `index.php` inclui `datatable-config.js` após o plugin
- [ ] Endpoint AJAX com formato server-side correto
- [ ] Colunas `orderable: false` para ações e campos não ordenáveis

## Quando usar

- Novo módulo CRUD (ver também skill `modulo-crud`)
- Ajuste de UX em tabelas existentes
- Code review de listagens admin
- Área de **acompanhamento** (`acompanhamento/*.php` + `js/acompanhamento.js`) — mesmo padrão visual e `NpsDataTable.create()`
