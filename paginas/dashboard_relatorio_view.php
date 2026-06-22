<?php
$pageTitle = 'Visualizar relatório';
$id = (int) ($_GET['id'] ?? 0);
require_once __DIR__ . '/../modules/dashboard/relatorios.php';
require_once __DIR__ . '/../modules/dashboard/home.php';

$rel = $id > 0 ? dashboard_relatorio_buscar($conn, $id) : null;
if (!$rel) {
    echo '<div class="alert alert-danger">Relatório não encontrado.</div>';
    return;
}

require_once __DIR__ . '/../modules/auth/permissoes.php';
if (!usuario_tem_acesso_evento($conn, (int) $rel['evento_id'])) {
    echo '<div class="alert alert-danger">Acesso negado a este relatório.</div>';
    return;
}
$pagina_voltar = eh_admin_master() ? 'dashboard_relatorios' : 'dashboard';
$label_voltar = eh_admin_master() ? 'Relatórios' : 'Dashboard';
$evento_meta = dashboard_evento_meta_resumo($rel);
?>
<div class="page-header dashboard-relatorio-header">
    <div class="dashboard-relatorio-header-main">
        <div class="dashboard-relatorio-top">
            <h1 class="page-title"><?= h($rel['nome']) ?></h1>
            <?php dashboard_renderizar_meta_evento($evento_meta); ?>
        </div>
    </div>
    <div class="page-header-actions">
        <a href="ajax/dashboard_relatorios_extrato.php?id=<?= (int) $rel['id'] ?>" class="btn-secondary" id="btnExportarExtratoView">
            <i class="bi bi-file-earmark-spreadsheet"></i> Exportar extrato XLSX
        </a>
        <a href="index.php?p=<?= h($pagina_voltar) ?>" class="btn-secondary"><i class="bi bi-arrow-left"></i> <?= h($label_voltar) ?></a>
    </div>
</div>

<input type="hidden" id="relatorio_preview_id" value="<?= (int) $rel['id'] ?>">

<div id="dashboardPreview" class="dashboard-preview-grid"></div>
