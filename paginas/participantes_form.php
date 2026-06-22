<?php
$pageTitle = 'Participante';
$id = (int) ($_GET['id'] ?? 0);
require_once __DIR__ . '/../modules/auth/permissoes.php';
if ($id <= 0 && !eh_admin_master()) {
    header('Location: index.php?p=participantes');
    exit;
}
require_once __DIR__ . '/../modules/eventos/functions.php';
$eventos = evento_listar_opcoes($conn, obter_contrato_usuario());
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $id ? 'Editar participante' : 'Novo participante' ?></h1>
        <p class="page-subtitle">Nome e e-mail obrigatórios — vincule a um ou mais eventos</p>
    </div>
    <a href="index.php?p=participantes" class="btn-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card">
    <div class="card-body">
        <form id="formParticipante" class="form-model" novalidate>
            <input type="hidden" name="id" id="participante_id" value="<?= $id ?>">

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-person"></i> Dados pessoais</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required" for="nome_completo">Nome completo</label>
                        <input type="text" id="nome_completo" name="nome_completo" class="form-control" required maxlength="255">
                    </div>
                    <div class="form-group">
                        <label class="form-label required" for="email">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control" required maxlength="255">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="telefone">Telefone</label>
                        <input type="text" id="telefone" name="telefone" class="form-control mask-telefone" placeholder="(00) 00000-0000">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="data_nascimento">Data de nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-briefcase"></i> Profissional</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="cargo">Cargo</label>
                        <input type="text" id="cargo" name="cargo" class="form-control" maxlength="150">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="empresa">Empresa</label>
                        <input type="text" id="empresa" name="empresa" class="form-control" maxlength="255">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="estado">Estado</label>
                        <input type="text" id="estado" name="estado" class="form-control" maxlength="2" placeholder="UF">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cidade">Cidade</label>
                        <input type="text" id="cidade" name="cidade" class="form-control" maxlength="150">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group form-group-full">
                        <label class="form-label" for="linkedin">LinkedIn</label>
                        <input type="url" id="linkedin" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/...">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-calendar-event"></i> Eventos</h3>
                <div class="form-row">
                    <div class="form-group form-group-full">
                        <label class="form-label" for="eventos_ids">Eventos vinculados</label>
                        <select id="eventos_ids" name="eventos_ids[]" class="form-control form-select2" multiple data-placeholder="Selecione os eventos">
                            <?php foreach ($eventos as $e): ?>
                                <option value="<?= (int) $e['id'] ?>"><?= h($e['nome']) ?> — <?= h($e['contrato_nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-hint">Comportamento e plotagem ficam restritos ao contrato/evento.</span>
                    </div>
                </div>
            </div>

            <?php if ($id): ?>
            <div class="form-section" id="secaoPesquisasRespostas">
                <h3 class="form-section-title"><i class="bi bi-clipboard-check"></i> Respostas de pesquisas</h3>
                <p class="form-hint mb-2">Vinculadas por e-mail ao participante, dentro de cada evento.</p>
                <div id="listaPesquisasRespostas" class="pesquisas-respostas-list">
                    <p class="text-muted">Carregando...</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>
