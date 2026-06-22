<?php
$pageTitle = 'Contrato';
$id = (int) ($_GET['id'] ?? 0);
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $id ? 'Editar contrato' : 'Novo contrato' ?></h1>
        <p class="page-subtitle">Nome e status do contrato</p>
    </div>
    <a href="index.php?p=contratos" class="btn-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card">
    <div class="card-body">
        <form id="formContrato" class="form-model" novalidate>
            <input type="hidden" name="id" id="contrato_id" value="<?= $id ?>">

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-file-earmark-text"></i> Identificação</h3>
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
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>
