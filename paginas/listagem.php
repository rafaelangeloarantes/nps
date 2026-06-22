<?php
/**
 * Conteúdo da página Listagem — incluído pelo index.php no Main.
 * Exemplo de estrutura para módulos CRUD com DataTable.
 */
$pageTitle = 'Listagem';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Listagem</h1>
        <p class="page-subtitle">Exemplo de listagem com DataTable padronizado</p>
    </div>
    <button type="button" class="btn-primary" id="btnNovo"><i class="bi bi-plus-lg"></i> Novo</button>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card">
    <div class="card-body">
        <div class="table-wrapper dt-wrapper">
            <table id="tabelaExemplo" class="display" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Criado em</th>
                        <th class="dt-no-sort">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
