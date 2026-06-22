<?php
$pageTitle = 'Credenciamento';
$id = (int) ($_GET['id'] ?? 0);
require_once __DIR__ . '/../modules/auth/permissoes.php';
if ($id <= 0 && !eh_admin_master()) {
    header('Location: index.php?p=credenciamentos');
    exit;
}
require_once __DIR__ . '/../modules/eventos/functions.php';
$eventos = evento_listar_opcoes($conn, obter_contrato_usuario());
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $id ? 'Editar credenciamento' : 'Novo credenciamento' ?></h1>
        <p class="page-subtitle">Status SHOW/NOSHOW do participante no evento (1 participante + 1 evento)</p>
    </div>
    <a href="index.php?p=credenciamentos" class="btn-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card">
    <div class="card-body">
        <form id="formCredenciamento" class="form-model" novalidate>
            <input type="hidden" name="id" id="credenciamento_id" value="<?= $id ?>">

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-person-check"></i> Vínculo</h3>
                <div class="form-row">
                    <div class="form-group form-group-full">
                        <label class="form-label required" for="evento_id">Evento</label>
                        <select id="evento_id" name="evento_id" class="form-control form-select2" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($eventos as $e): ?>
                                <option value="<?= (int) $e['id'] ?>"><?= h($e['nome']) ?> — <?= h($e['contrato_nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group form-group-full">
                        <label class="form-label required" for="participante_id">Participante</label>
                        <select id="participante_id" name="participante_id" class="form-control form-select2" required></select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required" for="status">Status</label>
                        <select id="status" name="status" class="form-control form-select2" required>
                            <option value="SHOW">SHOW</option>
                            <option value="NOSHOW">NOSHOW</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>
