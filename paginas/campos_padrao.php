<?php
$pageTitle = 'Campos Padrão NPS';
require_once __DIR__ . '/../modules/auth/permissoes.php';
if (!eh_admin_master()) {
    echo '<div class="alert alert-danger">Acesso restrito ao Administrador Master.</div>';
    return;
}
require_once __DIR__ . '/../modules/campos_padrao/functions.php';
$categorias = campo_padrao_categorias();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Campos Padrão NPS</h1>
        <p class="page-subtitle">Estrutura central de dados — vincule campos importados via De/Para nos mapeamentos</p>
    </div>
    <a href="index.php?p=campos_padrao_form" class="btn-primary"><i class="bi bi-plus-lg"></i> Novo campo padrão</a>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card mb-3">
    <div class="card-body">
        <div class="form-inline-filter">
            <div class="filter-field">
                <label for="filtroCategoria">Categoria</label>
                <select id="filtroCategoria" class="form-control form-select2">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $val => $label): ?>
                        <option value="<?= h($val) ?>"><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-wrapper dt-wrapper">
            <table id="tabelaCamposPadrao" class="display" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Categoria</th>
                        <th>Tipo dado</th>
                        <th>Gráfico</th>
                        <th>Sistema</th>
                        <th>Ordem</th>
                        <th class="dt-no-sort">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
