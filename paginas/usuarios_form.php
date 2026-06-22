<?php
$pageTitle = 'Usuário';
$id = (int) ($_GET['id'] ?? 0);
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/contratos/functions.php';
$contratos = contrato_listar_opcoes($conn);
$perfis = perfis_disponiveis();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $id ? 'Editar usuário' : 'Novo usuário' ?></h1>
        <p class="page-subtitle">Perfil e contrato vinculado. Usuários acessam apenas relatórios do contrato.</p>
    </div>
    <a href="index.php?p=usuarios" class="btn-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card">
    <div class="card-body">
        <form id="formUsuario" class="form-model" novalidate>
            <input type="hidden" name="id" id="usuario_id" value="<?= $id ?>">

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-person"></i> Identificação</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required" for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" class="form-control" required maxlength="255">
                    </div>
                    <div class="form-group">
                        <label class="form-label required" for="email">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control" required maxlength="255" autocomplete="off">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label <?= $id ? '' : 'required' ?>" for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" class="form-control" <?= $id ? '' : 'required' ?> autocomplete="new-password" placeholder="<?= $id ? 'Deixe em branco para manter' : '' ?>">
                        <small class="form-hint">Mín. 8 caracteres, maiúscula, minúscula, número e caractere especial.</small>
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

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-shield-check"></i> Perfil e contrato</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required" for="perfil">Perfil</label>
                        <select id="perfil" name="perfil" class="form-control form-select2" required>
                            <?php foreach ($perfis as $valor => $label): ?>
                                <option value="<?= h($valor) ?>"><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="grupoContrato">
                        <label class="form-label required" for="contrato_id">Contrato vinculado</label>
                        <select id="contrato_id" name="contrato_id" class="form-control form-select2">
                            <option value="">Selecione...</option>
                            <?php foreach ($contratos as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= h($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <p class="form-hint"><i class="bi bi-info-circle"></i> O perfil <strong>Usuário</strong> visualiza e exporta relatórios de todos os eventos do contrato vinculado.</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>
