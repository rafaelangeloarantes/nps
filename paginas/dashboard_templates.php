<?php
$pageTitle = 'Templates de Dashboard';
require_once __DIR__ . '/../modules/auth/permissoes.php';
if (!eh_admin_master()) {
    echo '<div class="alert alert-danger">Acesso restrito ao Administrador Master.</div>';
    return;
}
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Templates de Dashboard</h1>
        <p class="page-subtitle">Modelos reutilizáveis de relatório — aloque gráficos e grades sem vincular a um evento</p>
    </div>
    <a href="index.php?p=dashboard_templates_form" class="btn-primary"><i class="bi bi-plus-lg"></i> Novo template</a>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card">
    <div class="card-body">
        <div class="table-wrapper dt-wrapper">
            <table id="tabelaDashboardTemplates" class="display" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Relatórios</th>
                        <th>Criado em</th>
                        <th class="dt-no-sort">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
