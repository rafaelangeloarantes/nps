<?php
$pageTitle = 'Mapeamento de campos';
$pesquisa_id = (int) ($_GET['id'] ?? 0);
require_once __DIR__ . '/../modules/pesquisas/functions.php';

$pesquisa = $pesquisa_id > 0 ? pesquisa_buscar_por_id($conn, $pesquisa_id) : null;
if (!$pesquisa) {
    echo '<div class="alert alert-danger">Pesquisa não encontrada.</div>';
    return;
}

$tem_mapeamento_salvo = !empty(pesquisa_campo_listar($conn, $pesquisa_id));
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Mapeamento de campos</h1>
        <p class="page-subtitle">
            <?= h($pesquisa['nome']) ?> — Identificador: <strong><?= h($pesquisa['identificador_integracao'] ?: '—') ?></strong>
            — Evento: <strong><?= h($pesquisa['evento_nome']) ?></strong>
        </p>
    </div>
    <div class="page-header-actions">
        <a href="index.php?p=pesquisas" class="btn-secondary"><i class="bi bi-arrow-left"></i> Pesquisas</a>
        <button type="button" class="btn-secondary" id="btnDescobrirCampos">
            <i class="bi bi-cloud-download"></i> Buscar Campos
        </button>
        <button type="button" class="btn-secondary" id="btnSalvarMapeamento">
            <i class="bi bi-check-lg"></i> Salvar mapeamento
        </button>
        <button type="button" class="btn-primary<?= $tem_mapeamento_salvo ? '' : ' hidden' ?>" id="btnSyncPesquisa">
            <i class="bi bi-arrow-repeat"></i> Sincronizar respostas
        </button>
    </div>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<input type="hidden" id="pesquisa_id" value="<?= $pesquisa_id ?>">

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Campos da pesquisa</h2>
    </div>
    <div class="card-body">
        <div class="table-wrapper">
            <table id="tabelaCampos" class="display" width="100%">
                <thead>
                    <tr>
                        <th>Importar</th>
                        <th>Campo (API)</th>
                        <th>Exemplo</th>
                        <th>Label exibição</th>
                        <th>Campo padrão NPS</th>
                    </tr>
                </thead>
                <tbody id="tbodyCampos">
                    <tr><td colspan="5" class="text-muted">Clique em &quot;Buscar Campos&quot; para carregar os campos.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
