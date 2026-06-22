<?php
/**
 * Conteúdo do Dashboard — incluído pelo index.php no Main.
 */
$pageTitle = 'Dashboard';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Modelo System Designer — use como base para novos projetos.</p>
    </div>
    <button type="button" class="btn-secondary" data-modal-open="modalExemplo" aria-label="Abrir modal de exemplo">
        <i class="bi bi-window-stack"></i> Abrir modal
    </button>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-card-icon yellow"><i class="bi bi-currency-dollar"></i></div>
        <div class="stat-card-body">
            <span class="stat-card-label">Total esperado</span>
            <span class="stat-card-value">R$ 60.000</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon green"><i class="bi bi-bank"></i></div>
        <div class="stat-card-body">
            <span class="stat-card-label">Último investido</span>
            <span class="stat-card-value">R$ 50.000</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon blue"><i class="bi bi-file-earmark-text"></i></div>
        <div class="stat-card-body">
            <span class="stat-card-label">Total ganhos</span>
            <span class="stat-card-value">R$ 100.000</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon orange"><i class="bi bi-percent"></i></div>
        <div class="stat-card-body">
            <span class="stat-card-label">Taxa média</span>
            <span class="stat-card-value">10%</span>
        </div>
    </div>
</div>

<div class="chart-grid">
    <div class="card-chart">
        <div class="card-chart-header">
            <h2 class="card-chart-title"><i class="bi bi-pie-chart"></i> Resultado por categoria</h2>
            <a href="#" class="card-chart-link">Ver tudo <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="card-chart-body">
            <div class="chart-donut-wrap" data-chart-donut='{"labels":["Correto","Errado","Pulado"],"values":[624,96,80],"totalLabel":"Total"}'>
                <div class="chart-donut-canvas-wrap">
                    <canvas id="chartDonutResultado" width="180" height="180"></canvas>
                    <div class="chart-donut-center">
                        <span class="chart-donut-center-value">—</span>
                        <span class="chart-donut-center-label">Total</span>
                    </div>
                </div>
                <ul class="chart-donut-legend"></ul>
            </div>
        </div>
    </div>
    <div class="card-chart">
        <div class="card-chart-header">
            <h2 class="card-chart-title">Portfolio 1</h2>
            <a href="#" class="card-chart-link">Ver tudo <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="card-chart-body">
            <div class="chart-donut-with-metrics" data-chart-donut='{"labels":["Aplicado","Rendimento"],"values":[50000,10000],"totalLabel":"Taxa"}'>
                <div class="chart-donut-canvas-wrap">
                    <canvas id="chartDonutPortfolio" width="140" height="140"></canvas>
                    <div class="chart-donut-center">
                        <span class="chart-donut-center-value">—</span>
                        <span class="chart-donut-center-label">Taxa</span>
                    </div>
                </div>
                <div class="chart-donut-metrics">
                    <div class="chart-donut-metric">
                        <span class="chart-donut-metric-bar" style="background:var(--primary)"></span>
                        <div>
                            <span class="chart-donut-metric-label">Valor aplicado</span>
                            <span class="chart-donut-metric-value">R$ 50.000</span>
                        </div>
                    </div>
                    <div class="chart-donut-metric">
                        <span class="chart-donut-metric-bar" style="background:var(--info)"></span>
                        <div>
                            <span class="chart-donut-metric-label">Valor esperado</span>
                            <span class="chart-donut-metric-value">R$ 60.000</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-chart">
    <div class="card-chart-header">
        <h2 class="card-chart-title"><i class="bi bi-people"></i> Clientes ativos</h2>
        <a href="#" class="card-chart-link">Ver tudo <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="card-chart-body">
        <ul class="bar-list" data-chart-bars='{"labels":["Reino Unido","Estados Unidos","Suécia","Turquia","Espanha"],"values":[12628,10628,8628,6628,3628]}'></ul>
    </div>
</div>

<div class="card-chart">
    <div class="card-chart-header">
        <h2 class="card-chart-title"><i class="bi bi-bar-chart-line"></i> Atividade por região</h2>
        <a href="#" class="card-chart-link">Ver tudo <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="card-chart-body">
        <div style="height:220px" data-chart-bar='{"labels":["Norte","Nordeste","Centro-Oeste","Sudeste","Sul"],"values":[110000,98000,140000,67236,52000]}'>
            <canvas id="chartBarRegiao"></canvas>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Exemplo de tabela</h2>
        <div class="demo-notifications-buttons">
            <span class="demo-label">Toast:</span>
            <button type="button" class="btn-secondary btn-sm" id="btnToastSuccess"><i class="bi bi-check-lg"></i> Toast sucesso</button>
            <button type="button" class="btn-secondary btn-sm" id="btnToastError"><i class="bi bi-x-lg"></i> Toast erro</button>
            <button type="button" class="btn-secondary btn-sm" id="btnToastInfo"><i class="bi bi-info-lg"></i> Toast info</button>
            <span class="demo-label">Alert:</span>
            <button type="button" class="btn-secondary btn-sm" id="btnAlertSuccess"><i class="bi bi-check-lg"></i> Alert sucesso</button>
            <button type="button" class="btn-secondary btn-sm" id="btnAlertDanger"><i class="bi bi-x-lg"></i> Alert erro</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-wrapper dt-wrapper">
            <table id="dataTableExemplo" class="display" width="100%">
                <thead>
                    <tr><th>Nome</th><th>Status</th><th>Data</th><th class="dt-no-sort">Ações</th></tr>
                </thead>
                <tbody>
                    <tr><td>Item 1</td><td><span class="badge badge-active">Ativo</span></td><td>09/03/2026</td><td><button type="button" class="btn-icon btn-icon-sm btn-edit" aria-label="Editar"><i class="bi bi-pencil"></i></button> <button type="button" class="btn-icon btn-icon-sm btn-delete" aria-label="Excluir"><i class="bi bi-trash"></i></button></td></tr>
                    <tr><td>Item 2</td><td><span class="badge badge-pending">Pendente</span></td><td>08/03/2026</td><td><button type="button" class="btn-icon btn-icon-sm btn-edit" aria-label="Editar"><i class="bi bi-pencil"></i></button> <button type="button" class="btn-icon btn-icon-sm btn-delete" aria-label="Excluir"><i class="bi bi-trash"></i></button></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
