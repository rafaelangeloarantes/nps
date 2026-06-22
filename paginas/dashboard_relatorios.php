<?php
$pageTitle = 'Relatórios de Dashboard';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/eventos/functions.php';
$contrato_usuario = obter_contrato_usuario();
$eventos = evento_listar_opcoes($conn, $contrato_usuario);
$pode_criar = eh_admin_master();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Relatórios</h1>
        <p class="page-subtitle"><?= $pode_criar ? 'Instâncias de template vinculadas a um evento — com link e chave para compartilhamento externo' : 'Visualize e exporte relatórios de todos os eventos do seu contrato' ?></p>
    </div>
    <?php if ($pode_criar): ?>
    <a href="index.php?p=dashboard_relatorios_form" class="btn-primary"><i class="bi bi-plus-lg"></i> Novo relatório</a>
    <?php endif; ?>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<?php if (count($eventos) > 1): ?>
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
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-wrapper dt-wrapper">
            <table id="tabelaDashboardRelatorios" class="display" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Template</th>
                        <th>Evento</th>
                        <th>Chave</th>
                        <th>Último acesso</th>
                        <th class="dt-no-sort">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
