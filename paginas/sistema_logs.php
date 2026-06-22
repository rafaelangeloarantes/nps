<?php
$pageTitle = 'Log do Sistema';

require_once __DIR__ . '/../modules/log/functions.php';
log_garantir_estrutura($conn);

$usuarios_filtro = log_listar_usuarios_filtro($conn);
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Log do Sistema</h1>
        <p class="page-subtitle">Ações de usuários, integrações e erros registrados automaticamente</p>
    </div>
</div>

<div class="card log-filtros-card">
    <div class="card-body">
        <form id="formFiltrosLog" class="log-filtros-form" autocomplete="off">
            <div class="log-filtros-grid">
                <div class="form-group">
                    <label for="filtro_tipo">Tipo</label>
                    <select id="filtro_tipo" name="tipo" class="form-control">
                        <option value="">Todos</option>
                        <option value="acao">Ação</option>
                        <option value="integracao">Integração</option>
                        <option value="erro">Erro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filtro_nivel">Nível</label>
                    <select id="filtro_nivel" name="nivel" class="form-control">
                        <option value="">Todos</option>
                        <option value="info">Info</option>
                        <option value="aviso">Aviso</option>
                        <option value="erro">Erro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filtro_modulo">Módulo</label>
                    <input type="text" id="filtro_modulo" name="modulo" class="form-control" placeholder="Ex: eventos, usuarios">
                </div>
                <div class="form-group">
                    <label for="filtro_usuario">Usuário</label>
                    <select id="filtro_usuario" name="usuario_id" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios_filtro as $u): ?>
                        <option value="<?= (int) $u['id'] ?>"><?= h($u['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filtro_data_inicio">De</label>
                    <input type="date" id="filtro_data_inicio" name="data_inicio" class="form-control">
                </div>
                <div class="form-group">
                    <label for="filtro_data_fim">Até</label>
                    <input type="date" id="filtro_data_fim" name="data_fim" class="form-control">
                </div>
            </div>
            <div class="log-filtros-acoes">
                <button type="submit" class="btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
                <button type="button" id="btnLimparFiltrosLog" class="btn-secondary">Limpar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-wrapper dt-wrapper">
            <table id="tabelaLogs" class="display" width="100%">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Tipo</th>
                        <th>Nível</th>
                        <th>Módulo</th>
                        <th>Ação</th>
                        <th>Usuário</th>
                        <th>Mensagem</th>
                        <th class="dt-no-sort">Detalhes</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalLogDetalhe" aria-hidden="true">
    <div class="modal modal-lg" role="dialog" aria-labelledby="modalLogDetalhe-title">
        <div class="modal-header">
            <h2 class="modal-title" id="modalLogDetalhe-title">Detalhes do log</h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Fechar"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body" id="modalLogDetalheBody">
            <p class="text-muted">Carregando...</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" data-modal-close>Fechar</button>
        </div>
    </div>
</div>
