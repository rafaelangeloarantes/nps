<?php
/**
 * Index único — NPS Relatórios
 */
session_start();

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/modules/auth/middleware.php';
require_once __DIR__ . '/modules/auth/permissoes.php';

$app_name = $_ENV['APP_NAME'] ?? 'NPS Relatórios';

$pagina = isset($_GET['p']) ? trim($_GET['p']) : '';
$paginasPermitidas = [
    'dashboard',
    'contratos',
    'contratos_form',
    'eventos',
    'eventos_form',
    'eventos_mapeamento',
    'participantes',
    'participantes_form',
    'credenciamentos',
    'credenciamentos_form',
    'pesquisas',
    'pesquisas_form',
    'pesquisas_mapeamento',
    'dashboard_templates',
    'dashboard_templates_form',
    'dashboard_relatorios',
    'dashboard_relatorios_form',
    'dashboard_relatorio_view',
    'campos_padrao',
    'campos_padrao_form',
    'configuracoes',
    'usuarios',
    'usuarios_form',
    'sistema_logs',
];
if (!in_array($pagina, $paginasPermitidas, true)) {
    $pagina = pagina_inicial_usuario();
}

if (!usuario_pode_acessar_pagina($pagina)) {
    $pagina = pagina_inicial_usuario();
}

if (in_array($pagina, ['usuarios', 'usuarios_form', 'sistema_logs'], true) && !eh_admin_master()) {
    $pagina = 'dashboard';
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$pageTitle = 'Dashboard';
ob_start();
require __DIR__ . '/paginas/' . $pagina . '.php';
$mainContent = ob_get_clean();

$modulosJs = [
    'contratos' => 'contratos.js',
    'contratos_form' => 'contratos.js',
    'eventos' => 'eventos.js',
    'eventos_form' => 'eventos.js',
    'eventos_mapeamento' => 'eventos_mapeamento.js',
    'participantes' => 'participantes.js',
    'participantes_form' => 'participantes.js',
    'credenciamentos' => 'credenciamentos.js',
    'credenciamentos_form' => 'credenciamentos.js',
    'pesquisas' => 'pesquisas.js',
    'pesquisas_form' => 'pesquisas.js',
    'pesquisas_mapeamento' => 'pesquisas_mapeamento.js',
    'dashboard_templates' => 'dashboard_templates.js',
    'dashboard_templates_form' => 'dashboard_templates.js',
    'dashboard_relatorios' => 'dashboard_relatorios.js',
    'dashboard_relatorios_form' => 'dashboard_relatorios.js',
    'dashboard_relatorio_view' => 'dashboard_relatorios.js',
    'campos_padrao' => 'campos_padrao.js',
    'campos_padrao_form' => 'campos_padrao.js',
    'dashboard' => 'dashboard.js',
    'configuracoes' => 'configuracoes.js',
    'usuarios' => 'usuarios.js',
    'usuarios_form' => 'usuarios.js',
    'sistema_logs' => 'sistema_logs.js',
];
$jsModulo = $modulosJs[$pagina] ?? null;
$usaPesquisasRespostas = in_array($pagina, ['participantes', 'participantes_form'], true);
$usaCampoPadraoSelect = in_array($pagina, ['eventos_mapeamento', 'pesquisas_mapeamento'], true);
$usaDashboardRender = in_array($pagina, [
    'dashboard_relatorios_form',
    'dashboard_relatorio_view',
], true);
$ehMaster = eh_admin_master();
$authContexto = auth_contexto_js();

function nav_active($pagina, $targets)
{
    return in_array($pagina, (array) $targets, true) ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> — <?= h($app_name) ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%234F46E5'><path d='M3 3v18h18'/><path d='M7 16l4-8 4 5 5-9'/></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css">
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/form.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/datatable-override.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/charts.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/dashboard.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/modal.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/loading.css')) ?>">
<?php if ($usaPesquisasRespostas): ?>
    <link rel="stylesheet" href="<?= h(asset('css/pesquisas-respostas.css')) ?>">
<?php endif; ?>
<?php if ($usaCampoPadraoSelect): ?>
<link rel="stylesheet" href="<?= h(asset('css/campos-padrao.css')) ?>">
<?php endif; ?>
<?php if ($pagina === 'sistema_logs'): ?>
<link rel="stylesheet" href="<?= h(asset('css/sistema_logs.css')) ?>">
<?php endif; ?>
</head>
<body class="panel-page" id="bodyPanel">

<aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu principal">
    <div class="sidebar-brand">
        <img src="<?= h(asset('logotipo.png')) ?>" alt="<?= h($app_name) ?>" class="brand-logo brand-logo-sidebar">
    </div>

    <nav class="sidebar-nav">
        <?php if ($ehMaster): ?>
        <a href="index.php" class="nav-item<?= nav_active($pagina, 'dashboard') ?>">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>
        <a href="index.php?p=contratos" class="nav-item<?= nav_active($pagina, ['contratos', 'contratos_form']) ?>">
            <i class="bi bi-file-earmark-text"></i><span>Contratos</span>
        </a>
        <a href="index.php?p=eventos" class="nav-item<?= nav_active($pagina, ['eventos', 'eventos_form', 'eventos_mapeamento']) ?>">
            <i class="bi bi-calendar-event"></i><span>Eventos</span>
        </a>
        <a href="index.php?p=participantes" class="nav-item<?= nav_active($pagina, ['participantes', 'participantes_form']) ?>">
            <i class="bi bi-people"></i><span>Participantes</span>
        </a>
        <a href="index.php?p=credenciamentos" class="nav-item<?= nav_active($pagina, ['credenciamentos', 'credenciamentos_form']) ?>">
            <i class="bi bi-person-check"></i><span>Credenciamento</span>
        </a>
        <a href="index.php?p=pesquisas" class="nav-item<?= nav_active($pagina, ['pesquisas', 'pesquisas_form', 'pesquisas_mapeamento']) ?>">
            <i class="bi bi-clipboard-data"></i><span>Pesquisas</span>
        </a>
        <a href="index.php?p=dashboard_relatorios" class="nav-item<?= nav_active($pagina, ['dashboard_relatorios', 'dashboard_relatorios_form', 'dashboard_relatorio_view']) ?>">
            <i class="bi bi-graph-up"></i><span>Relatórios</span>
        </a>
        <?php endif; ?>
        <?php if (!$ehMaster): ?>
        <a href="index.php" class="nav-item<?= nav_active($pagina, 'dashboard') ?>">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>
        <a href="index.php?p=dashboard_relatorios" class="nav-item<?= nav_active($pagina, ['dashboard_relatorios', 'dashboard_relatorio_view']) ?>">
            <i class="bi bi-graph-up"></i><span>Relatórios</span>
        </a>
        <?php endif; ?>
        <?php if ($ehMaster): ?>
        <a href="index.php?p=dashboard_templates" class="nav-item<?= nav_active($pagina, ['dashboard_templates', 'dashboard_templates_form']) ?>">
            <i class="bi bi-layout-text-window-reverse"></i><span>Templates</span>
        </a>
        <a href="index.php?p=campos_padrao" class="nav-item<?= nav_active($pagina, ['campos_padrao', 'campos_padrao_form']) ?>">
            <i class="bi bi-diagram-3"></i><span>Campos Padrão</span>
        </a>
        <?php endif; ?>
        <?php if ($ehMaster): ?>
        <a href="index.php?p=usuarios" class="nav-item<?= nav_active($pagina, ['usuarios', 'usuarios_form']) ?>">
            <i class="bi bi-person-gear"></i><span>Usuários</span>
        </a>
        <a href="index.php?p=sistema_logs" class="nav-item<?= nav_active($pagina, 'sistema_logs') ?>">
            <i class="bi bi-journal-text"></i><span>Log do Sistema</span>
        </a>
        <a href="index.php?p=configuracoes" class="nav-item<?= nav_active($pagina, 'configuracoes') ?>">
            <i class="bi bi-gear"></i><span>Configurações</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><i class="bi bi-person-circle"></i></div>
            <div class="user-meta">
                <span class="user-name"><?= h($_SESSION['user_nome'] ?? 'Usuário') ?></span>
                <span class="user-role"><?= h(perfil_label($_SESSION['user_perfil'] ?? 'admin_master')) ?></span>
            </div>
        </div>
        <a href="logout.php" class="btn-logout" aria-label="Sair"><i class="bi bi-box-arrow-right"></i></a>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

<div class="main-wrapper" id="mainWrapper">
    <header class="topbar">
        <div class="topbar-left">
            <button type="button" class="btn-menu" id="btnMenu" aria-label="Abrir menu"><i class="bi bi-list"></i></button>
            <button type="button" class="btn-sidebar-toggle" id="btnSidebarToggle" aria-label="Contrair menu"><i class="bi bi-chevron-double-left" id="sidebarToggleIcon"></i></button>
        </div>
        <div class="topbar-right">
            <button type="button" class="btn-icon" id="btnDarkMode" aria-label="Modo escuro"><i class="bi bi-moon" id="darkModeIcon"></i></button>
        </div>
    </header>

    <main class="main-content" id="mainContent">
        <?= $mainContent ?>
    </main>
</div>

<div class="toast-container" id="toastContainer" role="region" aria-label="Notificações"></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= h(asset('js/loading.js')) ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="<?= h(asset('js/charts.js')) ?>"></script>
<script src="<?= h(asset('js/datatable-config.js')) ?>"></script>
<script src="<?= h(asset('js/main.js')) ?>"></script>
<script src="<?= h(asset('js/form.js')) ?>"></script>
<script src="<?= h(asset('js/modal.js')) ?>"></script>
<script src="<?= h(asset('js/crud-utils.js')) ?>"></script>
<script>window.NpsAuth = <?= json_encode($authContexto, JSON_UNESCAPED_UNICODE) ?>;</script>
<?php if ($usaPesquisasRespostas): ?>
<script src="<?= h(asset('js/pesquisas-respostas.js')) ?>"></script>
<?php endif; ?>
<?php if ($usaCampoPadraoSelect): ?>
<script src="<?= h(asset('js/campos-padrao-select.js')) ?>"></script>
<?php endif; ?>
<?php if ($usaDashboardRender): ?>
<script src="<?= h(asset('js/dashboard-render.js')) ?>"></script>
<?php endif; ?>
<?php if ($jsModulo): ?>
<script src="<?= h(asset('js/' . $jsModulo)) ?>"></script>
<?php endif; ?>
</body>
</html>
