<?php
$pageTitle = 'Mapeamento de atributos';
$evento_id = (int) ($_GET['id'] ?? 0);
require_once __DIR__ . '/../modules/eventos/functions.php';
require_once __DIR__ . '/../modules/eventos/atributos.php';

$evento = $evento_id > 0 ? evento_buscar_por_id($conn, $evento_id) : null;
if (!$evento) {
    echo '<div class="alert alert-danger">Evento não encontrado.</div>';
    return;
}

$tem_mapeamento_salvo = !empty(evento_atributo_listar($conn, $evento_id));
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Mapeamento de atributos</h1>
        <p class="page-subtitle">
            <?= h($evento['nome']) ?> — ID integração: <strong><?= h($evento['id_integracao'] ?: '—') ?></strong>
        </p>
    </div>
    <div class="page-header-actions">
        <a href="index.php?p=eventos" class="btn-secondary"><i class="bi bi-arrow-left"></i> Eventos</a>
        <button type="button" class="btn-secondary" id="btnDescobrirAtributos">
            <i class="bi bi-cloud-download"></i> Buscar Atributos
        </button>
        <button type="button" class="btn-secondary" id="btnSalvarMapeamento">
            <i class="bi bi-check-lg"></i> Salvar mapeamento
        </button>
        <button type="button" class="btn-primary<?= $tem_mapeamento_salvo ? '' : ' hidden' ?>" id="btnSyncParticipantes">
            <i class="bi bi-arrow-repeat"></i> Sincronizar participantes
        </button>
        <button type="button" class="btn-danger" id="btnLimparDadosEvento" data-evento-id="<?= $evento_id ?>">
            <i class="bi bi-trash3"></i> Limpar dados
        </button>
    </div>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<input type="hidden" id="evento_id" value="<?= $evento_id ?>">

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Atributos do evento</h2>
    </div>
    <div class="card-body">
        <div class="table-wrapper">
            <table id="tabelaAtributos" class="display" width="100%">
                <thead>
                    <tr>
                        <th>Importar</th>
                        <th>Atributo (API)</th>
                        <th>Exemplo</th>
                        <th>Campo padrão NPS</th>
                    </tr>
                </thead>
                <tbody id="tbodyAtributos">
                    <tr><td colspan="4" class="text-muted">Clique em &quot;Buscar Atributos&quot; para carregar os atributos.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
