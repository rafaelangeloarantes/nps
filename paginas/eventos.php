<?php
$pageTitle = 'Eventos';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/contratos/functions.php';
$contrato_usuario = obter_contrato_usuario();
$contratos = $contrato_usuario ? [] : contrato_listar_opcoes($conn);
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Eventos</h1>
        <p class="page-subtitle">Eventos vinculados a contratos</p>
    </div>
    <?php if (eh_admin_master()): ?>
    <a href="index.php?p=eventos_form" class="btn-primary"><i class="bi bi-plus-lg"></i> Novo evento</a>
    <?php endif; ?>
</div>

<?php if (!$contrato_usuario): ?>
<div class="card mb-3">
    <div class="card-body">
        <div class="form-inline-filter">
            <div class="filter-field">
                <label for="filtroContrato">Filtrar por contrato</label>
                <select id="filtroContrato" class="form-control form-select2">
                    <option value="">Todos os contratos</option>
                    <?php foreach ($contratos as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= h($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card">
    <div class="card-body">
        <div class="table-wrapper dt-wrapper">
            <table id="tabelaEventos" class="display" width="100%">
                <thead>
                    <tr>
                        <th>Evento ID</th>
                        <th>Nome</th>
                        <th>Convidados</th>
                        <th>Confirmados</th>
                        <th>Show</th>
                        <th>NoShow</th>
                        <th>Última sync</th>
                        <th class="dt-no-sort">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
