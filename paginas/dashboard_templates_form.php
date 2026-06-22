<?php

$pageTitle = 'Template de Dashboard';

require_once __DIR__ . '/../modules/auth/permissoes.php';

require_once __DIR__ . '/../modules/contratos/functions.php';

require_once __DIR__ . '/../modules/dashboard/templates.php';



if (!eh_admin_master()) {

    echo '<div class="alert alert-danger">Acesso restrito ao Administrador Master.</div>';

    return;

}



$id = (int) ($_GET['id'] ?? 0);

$contratos = contrato_listar_opcoes($conn);

$tipos_bloco = dashboard_tipos_bloco();

$tipos_grafico = dashboard_tipos_grafico();

$tipos_metrica = dashboard_tipos_metrica();
$colunas_linha_opcoes = dashboard_colunas_linha_opcoes();
$grafico_separadores = dashboard_grafico_separadores();
$fontes = dashboard_fontes_campo();

?>

<div class="page-header">

    <div>

        <h1 class="page-title"><?= $id ? 'Editar template' : 'Novo template' ?></h1>

        <p class="page-subtitle">Monte a estrutura do relatório com campos padrão NPS. Na criação do relatório, escolha o evento para preencher os dados.</p>

    </div>

    <a href="index.php?p=dashboard_templates" class="btn-secondary"><i class="bi bi-arrow-left"></i> Templates</a>

</div>



<div id="alertContainer" class="alert-placeholder"></div>



<input type="hidden" id="template_id" value="<?= $id ?>">



<div class="card mb-3">

    <div class="card-body">

        <form id="formTemplateMeta" class="form-model" novalidate>

            <div class="form-row">

                <div class="form-group form-group-2">

                    <label class="form-label required" for="nome">Nome do template</label>

                    <input type="text" id="nome" name="nome" class="form-control" required maxlength="255">

                </div>

                <div class="form-group form-group-2">

                    <label class="form-label" for="contrato_id">Contrato (opcional)</label>

                    <select id="contrato_id" name="contrato_id" class="form-control form-select2">

                        <option value="">Todos os contratos</option>

                        <?php foreach ($contratos as $c): ?>

                            <option value="<?= (int) $c['id'] ?>"><?= h($c['nome']) ?></option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>

            <div class="form-group form-group-full">

                <label class="form-label" for="descricao">Descrição</label>

                <textarea id="descricao" name="descricao" class="form-control" rows="2" maxlength="2000"></textarea>

            </div>

        </form>

    </div>

</div>



<div class="dashboard-builder">

    <div class="dashboard-builder-sidebar card">

        <div class="card-header">

            <h2 class="card-title" id="sidebarBlocoTitulo">Adicionar bloco</h2>

        </div>

        <div class="card-body">

            <div class="form-group">

                <label class="form-label">Tipo de bloco</label>

                <div class="dashboard-tipo-bloco-pills" id="tipoBlocoPills">

                    <?php foreach ($tipos_bloco as $val => $label): ?>

                        <button type="button" class="dashboard-pill<?= $val === 'contador' ? ' active' : '' ?>" data-tipo="<?= h($val) ?>"><?= h($label) ?></button>

                    <?php endforeach; ?>

                </div>

                <input type="hidden" id="bloco_tipo_bloco" value="contador">

            </div>



            <div class="form-group">

                <label class="form-label" for="bloco_fonte">Fonte de dados</label>

                <select id="bloco_fonte" class="form-control">

                    <?php foreach ($fontes as $val => $label): ?>

                        <option value="<?= h($val) ?>"><?= h($label) ?></option>

                    <?php endforeach; ?>

                </select>

                <small class="form-hint" id="hintFonte">Contadores e gráficos usam um campo. Grades trazem todos os campos da fonte.</small>

            </div>



            <div class="form-group" id="wrapCampo">

                <label class="form-label" for="bloco_campo">Campo padrão</label>

                <select id="bloco_campo" class="form-control"></select>

            </div>



            <div class="form-group hidden" id="wrapTipoGrafico">

                <label class="form-label" for="bloco_tipo_grafico">Tipo de gráfico</label>

                <select id="bloco_tipo_grafico" class="form-control">

                    <?php foreach ($tipos_grafico as $val => $label): ?>

                        <option value="<?= h($val) ?>"><?= h($label) ?></option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group hidden" id="wrapGraficoOpcoes">
                <label class="form-label" for="bloco_grafico_limite">Limite de itens no gráfico</label>
                <input type="number" id="bloco_grafico_limite" class="form-control" min="0" max="50" value="0" placeholder="0 = todos">
                <small class="form-hint">Ex.: 10 exibe os 10 primeiros; o restante agrupa em &quot;Outros&quot;.</small>

                <label class="form-label mt-2" for="bloco_grafico_separador">Separar valores múltiplos</label>
                <select id="bloco_grafico_separador" class="form-control">
                    <?php foreach ($grafico_separadores as $val => $label): ?>
                        <option value="<?= h($val) ?>"><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="form-hint">Útil quando o campo traz várias respostas no mesmo valor (ex.: ponto e vírgula).</small>
            </div>



            <div class="form-group hidden" id="wrapColunasLinha">
                <label class="form-label" for="bloco_colunas_linha">Colunas por linha</label>
                <select id="bloco_colunas_linha" class="form-control">
                    <?php foreach ($colunas_linha_opcoes as $val => $label): ?>
                        <option value="<?= (int) $val ?>"<?= (int) $val === 6 ? ' selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>



            <div class="form-group hidden" id="wrapTipoMetrica">

                <label class="form-label" for="bloco_tipo_metrica">Métrica do contador</label>

                <select id="bloco_tipo_metrica" class="form-control">

                    <?php foreach ($tipos_metrica as $val => $label): ?>

                        <option value="<?= h($val) ?>"><?= h($label) ?></option>

                    <?php endforeach; ?>

                </select>

            </div>



            <div class="form-group">

                <label class="form-label" for="bloco_titulo">Título de exibição</label>

                <input type="text" id="bloco_titulo" class="form-control" maxlength="255" placeholder="Preenchido automaticamente">

            </div>



            <button type="button" class="btn-primary btn-block" id="btnAdicionarBloco">

                <i class="bi bi-plus-circle"></i> Adicionar ao layout

            </button>

            <button type="button" class="btn-secondary btn-block hidden mt-3" id="btnCancelarEdicaoBloco">

                <i class="bi bi-x-lg"></i> Cancelar edição

            </button>

        </div>

    </div>



    <div class="dashboard-builder-canvas card">

        <div class="card-header">

            <h2 class="card-title">Layout do template</h2>

            <button type="button" class="btn-primary" id="btnSalvarTemplate">

                <i class="bi bi-check-lg"></i> Salvar template

            </button>

        </div>

        <div class="card-body">

            <p class="dashboard-layout-legend text-muted">

                <i class="bi bi-info-circle"></i>

                Arraste os blocos para reordenar. Clique em um bloco para editar. Configure quantos blocos cabem por linha (2 a 6). Grades usam a linha inteira.

            </p>

            <div id="widgetsCanvas" class="dashboard-widgets-canvas dashboard-layout-preview">

                <p class="text-muted" id="widgetsEmpty">Nenhum bloco adicionado. Use o painel ao lado para montar o relatório.</p>

            </div>

        </div>

    </div>

</div>

