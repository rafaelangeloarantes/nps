<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/dashboard/home.php';

$contrato_usuario = obter_contrato_usuario();
$cards_eventos = dashboard_listar_cards_eventos($conn, $contrato_usuario);
$eh_master = eh_admin_master();

$stats = [];
if ($eh_master) {
    $stats = [
        'contratos' => (int) mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS t FROM contratos WHERE ativo=1'))['t'],
        'eventos' => (int) mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS t FROM eventos WHERE ativo=1'))['t'],
        'participantes' => (int) mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS t FROM participantes WHERE ativo=1'))['t'],
        'relatorios' => (int) mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS t FROM dashboard_relatorios WHERE ativo=1'))['t'],
    ];
}
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Visão geral dos eventos e relatórios do seu contrato</p>
    </div>
    <?php if (usuario_pode_ver_relatorios()): ?>
    <a href="index.php?p=dashboard_relatorios" class="btn-secondary"><i class="bi bi-list-ul"></i> Ver lista de relatórios</a>
    <?php endif; ?>
</div>

<?php if ($eh_master): ?>
<div class="stat-cards stat-cards-compact mb-3">
    <div class="stat-card">
        <div class="stat-card-icon blue"><i class="bi bi-file-earmark-text"></i></div>
        <div class="stat-card-body">
            <span class="stat-card-label">Contratos</span>
            <span class="stat-card-value"><?= $stats['contratos'] ?></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon green"><i class="bi bi-calendar-event"></i></div>
        <div class="stat-card-body">
            <span class="stat-card-label">Eventos</span>
            <span class="stat-card-value"><?= $stats['eventos'] ?></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon yellow"><i class="bi bi-people"></i></div>
        <div class="stat-card-body">
            <span class="stat-card-label">Participantes</span>
            <span class="stat-card-value"><?= $stats['participantes'] ?></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon orange"><i class="bi bi-graph-up"></i></div>
        <div class="stat-card-body">
            <span class="stat-card-label">Relatórios</span>
            <span class="stat-card-value"><?= $stats['relatorios'] ?></span>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="dashboard-eventos-section">
    <?php dashboard_renderizar_cards_eventos($cards_eventos); ?>
</div>
