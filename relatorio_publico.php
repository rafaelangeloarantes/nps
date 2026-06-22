<?php
/**
 * Visualização pública de relatório — requer token + chave de acesso
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/modules/dashboard/relatorios.php';

dashboard_publico_iniciar_sessao();

$token = trim($_GET['t'] ?? '');
$app_name = $_ENV['APP_NAME'] ?? 'NPS Relatórios';
$rel_publico = $token !== '' ? dashboard_relatorio_buscar_por_token($conn, $token) : null;
$chave_prefixo_hint = trim((string) ($rel_publico['chave_prefixo'] ?? ''));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório — <?= h($app_name) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/form.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/charts.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/dashboard.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/loading.css')) ?>">
</head>
<body class="dashboard-public-page" id="bodyPanel">
<div id="publicAuth" class="dashboard-public-auth-wrap">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">
                <div class="login-brand-icon" aria-hidden="true"><i class="bi bi-shield-lock-fill"></i></div>
                <h1>Acesso ao relatório</h1>
                <p>Informe a chave de acesso fornecida pelo organizador.</p>
            </div>

            <div id="alertPublicAuth" class="alert-placeholder"></div>

            <form id="formChavePublica" class="form-model" novalidate>
                <input type="hidden" id="publicToken" value="<?= h($token) ?>">
                <div class="form-group">
                    <label class="form-label required" for="chave_publica">Chave de acesso</label>
                    <input
                        type="text"
                        id="chave_publica"
                        class="form-control dashboard-public-chave"
                        required
                        autocomplete="off"
                        maxlength="20"
                        placeholder="Ex.: CL2MXXXX"
                        inputmode="text"
                    >
                    <p class="form-hint" id="chaveHint"><?php if ($chave_prefixo_hint !== ''): ?>A chave começa com: <?= h($chave_prefixo_hint) ?><?php endif; ?></p>
                </div>
                <button type="submit" class="btn-primary btn-block">
                    <i class="bi bi-unlock"></i> Acessar relatório
                </button>
            </form>
        </div>
    </div>
</div>

<div id="publicDashboard" class="dashboard-public-shell hidden">
    <header class="dashboard-public-header dashboard-relatorio-header">
        <div class="dashboard-relatorio-header-main">
            <div class="dashboard-relatorio-top">
                <h1 class="page-title" id="publicTitulo">Relatório</h1>
                <div class="dashboard-relatorio-meta" id="publicEventoMeta"></div>
            </div>
        </div>
    </header>
    <div class="stat-cards" id="publicStats"></div>
    <div id="publicGrid" class="dashboard-preview-grid"></div>
    <p class="dashboard-public-footer">Visualização somente leitura — exportação disponível apenas para usuários logados.</p>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= h(asset('js/loading.js')) ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
<script src="<?= h(asset('js/charts.js')) ?>"></script>
<script src="<?= h(asset('js/dashboard-render.js')) ?>"></script>
<script src="<?= h(asset('js/dashboard-publico.js')) ?>"></script>
</body>
</html>
