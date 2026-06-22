<?php
$pageTitle = 'Credenciamento';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/eventos/functions.php';
$eventos = evento_listar_opcoes($conn, obter_contrato_usuario());
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Credenciamento</h1>
        <p class="page-subtitle">SHOW / NOSHOW por evento — sincronizado via API (campo NoShow) ou ajuste manual</p>
    </div>
    <?php if (eh_admin_master()): ?>
    <a href="index.php?p=credenciamentos_form" class="btn-primary"><i class="bi bi-plus-lg"></i> Novo registro</a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="form-inline-filter">
            <div class="filter-field">
                <label for="filtroEvento">Filtrar por evento</label>
                <select id="filtroEvento" class="form-control form-select2">
                    <option value="">Todos os eventos</option>
                    <?php foreach ($eventos as $e): ?>
                        <option value="<?= (int) $e['id'] ?>"><?= h($e['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card">
    <div class="card-body">
        <div class="table-wrapper dt-wrapper">
            <table id="tabelaCredenciamentos" class="display" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Evento</th>
                        <th>Participante</th>
                        <th>E-mail</th>
                        <th>Status</th>
                        <th>Última sync</th>
                        <th class="dt-no-sort">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
