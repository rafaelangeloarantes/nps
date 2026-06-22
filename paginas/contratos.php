<?php
$pageTitle = 'Contratos';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Contratos</h1>
        <p class="page-subtitle">Gestão de contratos — módulo único do sistema</p>
    </div>
    <a href="index.php?p=contratos_form" class="btn-primary"><i class="bi bi-plus-lg"></i> Novo contrato</a>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card">
    <div class="card-body">
        <div class="table-wrapper dt-wrapper">
            <table id="tabelaContratos" class="display" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th class="dt-no-sort">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
