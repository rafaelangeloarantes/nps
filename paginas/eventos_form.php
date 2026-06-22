<?php
$pageTitle = 'Evento';
$id = (int) ($_GET['id'] ?? 0);
require_once __DIR__ . '/../modules/auth/permissoes.php';
if ($id <= 0 && !eh_admin_master()) {
    header('Location: index.php?p=eventos');
    exit;
}
if ($id > 0 && !usuario_tem_acesso_evento($conn, $id)) {
    header('Location: index.php?p=eventos');
    exit;
}
require_once __DIR__ . '/../modules/contratos/functions.php';
$contratos = eh_admin_master() ? contrato_listar_opcoes($conn) : [];
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $id ? 'Editar evento' : 'Novo evento' ?></h1>
        <p class="page-subtitle">Dados do evento, integração e acesso externo</p>
    </div>
    <a href="index.php?p=eventos" class="btn-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card">
    <div class="card-body">
        <form id="formEvento" class="form-model" novalidate>
            <input type="hidden" name="id" id="evento_id" value="<?= $id ?>">

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-info-circle"></i> Dados gerais</h3>
                <div class="form-row">
                    <div class="form-group form-group-full">
                        <label class="form-label required" for="contrato_id">Contrato</label>
                        <select id="contrato_id" name="contrato_id" class="form-control form-select2" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($contratos as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= h($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required" for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" class="form-control" required maxlength="255">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ativo">Status</label>
                        <select id="ativo" name="ativo" class="form-control form-select2">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="id_integracao">ID de integração</label>
                        <input type="text" id="id_integracao" name="id_integracao" class="form-control" placeholder="Ex: 200655">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="clima">Clima</label>
                        <input type="text" id="clima" name="clima" class="form-control" placeholder="Ex: Ensolarado, 28°C">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-calendar-event"></i> Data e local</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="data_inicio">Início</label>
                        <input type="datetime-local" id="data_inicio" name="data_inicio" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="data_fim">Fim</label>
                        <input type="datetime-local" id="data_fim" name="data_fim" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group form-group-full">
                        <label class="form-label" for="endereco">Endereço</label>
                        <input type="text" id="endereco" name="endereco" class="form-control" maxlength="500">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-link-45deg"></i> API (opcional)</h3>
                <div class="form-row">
                    <div class="form-group form-group-full">
                        <label class="form-label" for="link">Link (URL base guests)</label>
                        <input type="url" id="link" name="link" class="form-control" placeholder="https://api-externa.inteegra.com.br/public">
                        <span class="form-hint">Sobrescreve a base de guests deste evento. Credenciais em <a href="index.php?p=configuracoes">Configurações</a>.</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
                <?php if ($id): ?>
                <a href="index.php?p=eventos_mapeamento&id=<?= $id ?>" class="btn-secondary">
                    <i class="bi bi-diagram-3"></i> Mapear atributos API
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($id): ?>
<div class="card card-danger-zone mt-3">
    <div class="card-header">
        <h2 class="card-title"><i class="bi bi-exclamation-triangle"></i> Zona de perigo</h2>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">
            Remove todos os vínculos de <strong>participantes</strong>, registros de <strong>credenciamento</strong>
            e <strong>pesquisas</strong> deste evento. O evento e o mapeamento de atributos da API são mantidos.
        </p>
        <button type="button" class="btn-danger" id="btnLimparDadosEvento" data-evento-id="<?= $id ?>">
            <i class="bi bi-trash3"></i> Limpar dados do evento
        </button>
    </div>
</div>
<?php endif; ?>
