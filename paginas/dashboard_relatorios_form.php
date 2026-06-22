<?php
$pageTitle = 'Relatório de Dashboard';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/eventos/functions.php';
require_once __DIR__ . '/../modules/dashboard/templates.php';

if (!eh_admin_master()) {
    header('Location: index.php?p=dashboard_relatorios');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$contrato_usuario = obter_contrato_usuario();
$eventos = evento_listar_opcoes($conn, $contrato_usuario);
$templates = dashboard_template_listar_opcoes($conn, $contrato_usuario);
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $id ? 'Editar relatório' : 'Novo relatório' ?></h1>
        <p class="page-subtitle">Selecione template e evento — ao salvar, será gerado link público e chave de acesso</p>
    </div>
    <a href="index.php?p=dashboard_relatorios" class="btn-secondary"><i class="bi bi-arrow-left"></i> Relatórios</a>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card mb-3">
    <div class="card-body">
        <form id="formRelatorio" class="form-model" novalidate>
            <input type="hidden" name="id" id="relatorio_id" value="<?= $id ?>">

            <div class="form-row">
                <div class="form-group form-group-2">
                    <label class="form-label required" for="nome">Nome do relatório</label>
                    <input type="text" id="nome" name="nome" class="form-control" required maxlength="255">
                </div>
                <div class="form-group form-group-2">
                    <label class="form-label required" for="template_id">Template</label>
                    <select id="template_id" name="template_id" class="form-control form-select2" required>
                        <option value="">Selecione…</option>
                        <?php foreach ($templates as $t): ?>
                            <option value="<?= (int) $t['id'] ?>"><?= h($t['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-group-2">
                    <label class="form-label required" for="evento_id">Evento</label>
                    <select id="evento_id" name="evento_id" class="form-control form-select2" required>
                        <option value="">Selecione…</option>
                        <?php foreach ($eventos as $e): ?>
                            <option value="<?= (int) $e['id'] ?>"><?= h($e['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group form-group-2" id="wrapRegenerarChave" style="display:none;">
                    <label class="form-label">&nbsp;</label>
                    <label class="form-check">
                        <input type="checkbox" name="regenerar_chave" id="regenerar_chave" value="1">
                        Regenerar chave de acesso
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar relatório</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-3 hidden" id="cardAcessoPublico">
    <div class="card-header">
        <h2 class="card-title"><i class="bi bi-link-45deg"></i> Acesso público</h2>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label class="form-label">Link do relatório</label>
            <div class="input-copy-group">
                <input type="text" id="url_publica" class="form-control" readonly>
                <button type="button" class="btn-secondary" id="btnCopiarLink" aria-label="Copiar link"><i class="bi bi-clipboard"></i></button>
            </div>
        </div>
        <div class="form-group" id="wrapChaveNova">
            <label class="form-label">Chave de acesso</label>
            <div class="input-copy-group">
                <input type="text" id="chave_acesso" class="form-control dashboard-public-chave" readonly placeholder="—">
                <button type="button" class="btn-secondary" id="btnCopiarChave" aria-label="Copiar chave"><i class="bi bi-clipboard"></i></button>
            </div>
            <p class="form-hint" id="chaveHintAdmin">A chave permanece a mesma até você marcar &quot;Regenerar chave de acesso&quot; e salvar.</p>
        </div>
    </div>
</div>

<div class="card hidden" id="cardPreview">
    <div class="card-header">
        <h2 class="card-title">Pré-visualização</h2>
        <div class="page-header-actions">
            <a href="#" id="btnExportarExtrato" class="btn-secondary"><i class="bi bi-file-earmark-spreadsheet"></i> Exportar extrato XLSX</a>
            <a href="#" id="btnAbrirPublico" class="btn-secondary" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Abrir link público</a>
        </div>
    </div>
    <div class="card-body">
        <div id="dashboardPreview" class="dashboard-preview-grid"></div>
    </div>
</div>
