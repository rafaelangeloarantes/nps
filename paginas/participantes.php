<?php
$pageTitle = 'Participantes';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/eventos/functions.php';
$eventos = evento_listar_opcoes($conn, obter_contrato_usuario());
$filtro_evento = (int) ($_GET['evento_id'] ?? 0);
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Participantes</h1>
        <p class="page-subtitle">Cadastro por e-mail — filtre por evento para ver SHOW, NOSHOW, CONVIDADO ou Pendente</p>
    </div>
    <?php if (eh_admin_master()): ?>
    <a href="index.php?p=participantes_form" class="btn-primary"><i class="bi bi-plus-lg"></i> Novo participante</a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body card-body--filter">
        <div class="form-inline-filter form-inline-filter--nowrap">
            <div class="filter-field filter-field--inline filter-field--grow">
                <label for="filtroEvento">Evento</label>
                <select id="filtroEvento" class="form-control form-select2">
                    <option value="">Todos</option>
                    <?php foreach ($eventos as $e): ?>
                        <option value="<?= (int) $e['id'] ?>"<?= $filtro_evento === (int) $e['id'] ? ' selected' : '' ?>><?= h($e['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field filter-field--inline filter-field--check">
                <label for="filtroComPesquisa" class="filter-check-label">
                    <input type="checkbox" id="filtroComPesquisa" value="1">
                    <span>Com pesquisa</span>
                </label>
            </div>
            <div class="filter-actions">
                <button type="button" class="btn-secondary btn-sm" id="btnSyncEventoParticipantes" disabled>
                    <i class="bi bi-arrow-repeat"></i> Sincronizar
                </button>
                <a href="#" class="btn-secondary btn-sm" id="btnMapearEvento" style="display:none">
                    <i class="bi bi-diagram-3"></i> Mapear
                </a>
            </div>
        </div>
    </div>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card">
    <div class="card-body">
        <div class="table-wrapper dt-wrapper">
            <table id="tabelaParticipantes" class="display" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Empresa</th>
                        <th>Integridade</th>
                        <th>Credenciamento</th>
                        <th class="dt-no-sort">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
