<?php
$pageTitle = 'Usuários';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Usuários</h1>
        <p class="page-subtitle">Gestão de usuários, perfis e permissões de acesso</p>
    </div>
    <a href="index.php?p=usuarios_form" class="btn-primary"><i class="bi bi-plus-lg"></i> Novo usuário</a>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<div class="card">
    <div class="card-body">
        <div class="table-wrapper dt-wrapper">
            <table id="tabelaUsuarios" class="display" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Contrato</th>
                        <th>Status</th>
                        <th>Último login</th>
                        <th class="dt-no-sort">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
