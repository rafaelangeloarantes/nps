<?php

$pageTitle = 'Pesquisa';

$id = (int) ($_GET['id'] ?? 0);

require_once __DIR__ . '/../modules/auth/permissoes.php';
if ($id <= 0 && !eh_admin_master()) {
    header('Location: index.php?p=pesquisas');
    exit;
}

require_once __DIR__ . '/../modules/eventos/functions.php';

$eventos = evento_listar_opcoes($conn, obter_contrato_usuario());

?>

<div class="page-header">

    <div>

        <h1 class="page-title"><?= $id ? 'Editar pesquisa' : 'Nova pesquisa' ?></h1>

        <p class="page-subtitle">Dados básicos — o mapeamento de campos é feito em etapa separada</p>

    </div>

    <a href="index.php?p=pesquisas" class="btn-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>

</div>



<div id="alertContainer" class="alert-placeholder"></div>



<div class="card">

    <div class="card-body">

        <form id="formPesquisa" class="form-model" novalidate>

            <input type="hidden" name="id" id="pesquisa_id" value="<?= $id ?>">



            <div class="form-section">

                <h3 class="form-section-title"><i class="bi bi-clipboard-data"></i> Dados da pesquisa</h3>

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

                    <div class="form-group">

                        <label class="form-label required" for="nome">Nome da pesquisa</label>

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

                    <div class="form-group form-group-full">

                        <label class="form-label required" for="identificador_integracao">Identificador de integração</label>

                        <input type="text" id="identificador_integracao" name="identificador_integracao" class="form-control" required maxlength="100">

                        <span class="form-hint">EventId enviado na consulta <code>/api/guests</code> — pode ser diferente do ID de integração do evento (participantes).</span>

                    </div>

                </div>

            </div>



            <div class="form-actions">

                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>

            </div>

        </form>

    </div>

</div>


