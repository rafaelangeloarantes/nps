<?php
$pageTitle = 'Campo Padrão NPS';
$id = (int) ($_GET['id'] ?? 0);
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/campos_padrao/functions.php';
if (!eh_admin_master()) {
    echo '<div class="alert alert-danger">Acesso restrito ao Administrador Master.</div>';
    return;
}
$categorias = campo_padrao_categorias();
$tipos_dado = campo_padrao_tipos_dado();
$tipos_grafico = campo_padrao_tipos_grafico();
$colunas_participante = [
    '' => 'Não mapeia coluna de participante',
    'nome_completo' => 'Nome completo',
    'email' => 'E-mail',
    'telefone' => 'Telefone',
    'cargo' => 'Cargo',
    'empresa' => 'Empresa',
    'estado' => 'Estado',
    'cidade' => 'Cidade',
    'data_nascimento' => 'Data de nascimento',
    'linkedin' => 'LinkedIn',
];
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $id ? 'Editar campo padrão' : 'Novo campo padrão' ?></h1>
        <p class="page-subtitle">Catálogo reutilizável para normalizar dados importados de qualquer evento ou pesquisa</p>
    </div>
    <a href="index.php?p=campos_padrao" class="btn-secondary"><i class="bi bi-arrow-left"></i> Campos padrão</a>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card">
    <div class="card-body">
        <form id="formCampoPadrao" class="form-model" novalidate>
            <input type="hidden" name="id" id="campo_padrao_id" value="<?= $id ?>">

            <div class="form-row">
                <div class="form-group form-group-2">
                    <label class="form-label required" for="nome">Nome de exibição</label>
                    <input type="text" id="nome" name="nome" class="form-control" required maxlength="255" placeholder="Ex.: Estado">
                </div>
                <div class="form-group form-group-2">
                    <label class="form-label" for="slug">Slug (chave)</label>
                    <input type="text" id="slug" name="slug" class="form-control" maxlength="100" placeholder="Gerado automaticamente se vazio">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-group-3">
                    <label class="form-label required" for="categoria">Categoria</label>
                    <select id="categoria" name="categoria" class="form-control form-select2" required>
                        <?php foreach ($categorias as $val => $label): ?>
                            <option value="<?= h($val) ?>"><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group form-group-3">
                    <label class="form-label required" for="tipo_dado">Tipo de dado</label>
                    <select id="tipo_dado" name="tipo_dado" class="form-control form-select2" required>
                        <?php foreach ($tipos_dado as $val => $label): ?>
                            <option value="<?= h($val) ?>"><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group form-group-3">
                    <label class="form-label" for="tipo_grafico_sugerido">Gráfico sugerido</label>
                    <select id="tipo_grafico_sugerido" name="tipo_grafico_sugerido" class="form-control form-select2">
                        <?php foreach ($tipos_grafico as $val => $label): ?>
                            <option value="<?= h($val) ?>"><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-group-2">
                    <label class="form-label" for="mapeia_participante">Mapeia coluna do participante</label>
                    <select id="mapeia_participante" name="mapeia_participante" class="form-control form-select2">
                        <?php foreach ($colunas_participante as $val => $label): ?>
                            <option value="<?= h($val) ?>"><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-hint">Quando preenchido, a sincronização grava direto na tabela de participantes.</small>
                </div>
                <div class="form-group form-group-2">
                    <label class="form-label" for="ordem">Ordem</label>
                    <input type="number" id="ordem" name="ordem" class="form-control" min="0" value="0">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>
