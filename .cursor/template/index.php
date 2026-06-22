<?php
/**
 * Index único — System Designer
 * Menu, topo, CSS e JS centralizados aqui. Conteúdo de cada tela é include em paginas/ via ?p=
 */
session_start();

require_once __DIR__ . '/includes/functions.php';

$pagina = isset($_GET['p']) ? trim($_GET['p']) : '';
$paginasPermitidas = ['dashboard', 'formularios', 'listagem', 'configuracoes'];
if (!in_array($pagina, $paginasPermitidas, true)) {
    $pagina = 'dashboard';
}

// Token CSRF: gerar se não existir (reutilizado até próximo submit de formulário protegido)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$pageTitle = 'Dashboard';
ob_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'paginas' . DIRECTORY_SEPARATOR . $pagina . '.php';
$mainContent = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> — System Designer</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%234F46E5'><path d='M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z'/></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css">
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/form.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/datatable-override.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/charts.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/modal.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/loading.css')) ?>">
</head>
<body class="panel-page" id="bodyPanel">

<!-- Sidebar -->
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu principal">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-grid-3x3-gap-fill"></i></div>
        <span class="brand-text">System Designer</span>
    </div>

    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item<?php echo ($pagina === 'dashboard') ? ' active' : ''; ?>" aria-label="Dashboard">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>
        <a href="index.php?p=listagem" class="nav-item<?php echo ($pagina === 'listagem') ? ' active' : ''; ?>" aria-label="Listagem">
            <i class="bi bi-table"></i><span>Listagem</span>
        </a>
        <a href="index.php?p=formularios" class="nav-item<?php echo ($pagina === 'formularios') ? ' active' : ''; ?>" aria-label="Formulários">
            <i class="bi bi-pencil-square"></i><span>Formulários</span>
        </a>
        <a href="index.php?p=configuracoes" class="nav-item<?php echo ($pagina === 'configuracoes') ? ' active' : ''; ?>" aria-label="Configurações">
            <i class="bi bi-gear"></i><span>Configurações</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><i class="bi bi-person-circle"></i></div>
            <div class="user-meta">
                <span class="user-name">Usuário</span>
                <span class="user-role">Admin</span>
            </div>
        </div>
        <a href="logout.php" class="btn-logout" id="btnLogout" aria-label="Sair" role="button">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

<div class="main-wrapper" id="mainWrapper">
    <header class="topbar">
        <div class="topbar-left">
            <button type="button" class="btn-menu" id="btnMenu" aria-label="Abrir menu"><i class="bi bi-list"></i></button>
            <button type="button" class="btn-sidebar-toggle" id="btnSidebarToggle" aria-label="Contrair ou expandir menu lateral"><i class="bi bi-chevron-double-left" id="sidebarToggleIcon"></i></button>
        </div>
        <div class="topbar-right">
            <button type="button" class="btn-icon" id="btnDarkMode" aria-label="Alternar modo escuro"><i class="bi bi-moon" id="darkModeIcon"></i></button>
        </div>
    </header>

    <main class="main-content" id="mainContent">
        <?php echo $mainContent; ?>
    </main>
</div>

<div class="toast-container" id="toastContainer" role="region" aria-label="Notificações"></div>

<div class="modal-overlay" id="modalExemplo" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalExemplo-title">
        <div class="modal-header">
            <h2 class="modal-title" id="modalExemplo-title">Exemplo de modal</h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Fechar"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
            <p>Conteúdo do modal. Use <code>data-modal-open="modalExemplo"</code> em um botão para abrir.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" data-modal-close>Cancelar</button>
            <button type="button" class="btn-primary">Confirmar</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="<?= h(asset('js/loading.js')) ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-maskmoney@3.0.2/dist/jquery.maskMoney.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="<?= h(asset('js/charts.js')) ?>"></script>
<script src="<?= h(asset('js/datatable-config.js')) ?>"></script>
<script src="<?= h(asset('js/main.js')) ?>"></script>
<script src="<?= h(asset('js/form.js')) ?>"></script>
<script src="<?= h(asset('js/modal.js')) ?>"></script>
</body>
</html>
