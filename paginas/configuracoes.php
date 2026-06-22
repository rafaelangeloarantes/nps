<?php
/**
 * Configurações — integrações externas centralizadas
 */
$pageTitle = 'Configurações';
require_once __DIR__ . '/../modules/integracoes/functions.php';
require_once __DIR__ . '/../modules/manutencao/limpeza.php';
$integracoes = integracao_listar($conn);
$limpezaOpcoes = manutencao_opcoes_limpeza();
$limpezaSugeridas = manutencao_opcoes_sugeridas();
$limpezaAbsorvidas = manutencao_opcoes_absorvidas_por_eventos();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Configurações</h1>
        <p class="page-subtitle">Credenciais e URLs das integrações externas do sistema</p>
    </div>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<?php if (empty($integracoes)): ?>
<div class="card">
    <div class="card-body">
        <p class="text-muted mb-0">Nenhuma integração cadastrada.</p>
    </div>
</div>
<?php else: ?>
    <?php foreach ($integracoes as $integracao): ?>
    <div class="card mb-3">
        <div class="card-header">
            <h2 class="card-title">
                <i class="bi bi-plug"></i> <?= h($integracao['nome']) ?>
                <?php if ((int) $integracao['ativo'] === 1): ?>
                    <span class="badge badge-active ms-2">Ativa</span>
                <?php else: ?>
                    <span class="badge badge-inactive ms-2">Inativa</span>
                <?php endif; ?>
            </h2>
            <?php if (!empty($integracao['url_documentacao'])): ?>
            <a href="<?= h($integracao['url_documentacao']) ?>" class="btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-book"></i> Documentação da API
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form class="form-model form-integracao" data-id="<?= (int) $integracao['id'] ?>" data-codigo="<?= h($integracao['codigo']) ?>" novalidate>
                <input type="hidden" name="id" value="<?= (int) $integracao['id'] ?>">

                <?php if (!empty($integracao['descricao'])): ?>
                <p class="text-muted mb-3"><?= h($integracao['descricao']) ?></p>
                <?php endif; ?>

                <div class="form-section">
                    <h3 class="form-section-title"><i class="bi bi-link-45deg"></i> URLs da API</h3>
                    <div class="form-row">
                        <div class="form-group form-group-full">
                            <label class="form-label" for="url_auth_base_<?= (int) $integracao['id'] ?>">URL base — autenticação (token)</label>
                            <input type="url" id="url_auth_base_<?= (int) $integracao['id'] ?>" name="url_auth_base" class="form-control"
                                   value="<?= h($integracao['url_auth_base'] ?? '') ?>"
                                   placeholder="https://api-externa.inteegra.com.br/security/security">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group form-group-full">
                            <label class="form-label" for="url_api_base_<?= (int) $integracao['id'] ?>">URL base — API de dados (guests)</label>
                            <input type="url" id="url_api_base_<?= (int) $integracao['id'] ?>" name="url_api_base" class="form-control"
                                   value="<?= h($integracao['url_api_base'] ?? '') ?>"
                                   placeholder="https://api-externa.inteegra.com.br/public">
                            <span class="form-hint">Eventos podem sobrescrever apenas a base de guests via campo Link.</span>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group form-group-full">
                            <label class="form-label" for="url_documentacao_<?= (int) $integracao['id'] ?>">Documentação da API</label>
                            <input type="url" id="url_documentacao_<?= (int) $integracao['id'] ?>" name="url_documentacao" class="form-control"
                                   value="<?= h($integracao['url_documentacao'] ?? '') ?>"
                                   placeholder="https://documenter.getpostman.com/...">
                            <span class="form-hint">Link de referência para consulta dos endpoints e parâmetros.</span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title"><i class="bi bi-shield-lock"></i> Credenciais de acesso</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="usuario_acesso_<?= (int) $integracao['id'] ?>">Usuário</label>
                            <input type="text" id="usuario_acesso_<?= (int) $integracao['id'] ?>" name="usuario_acesso" class="form-control"
                                   value="<?= h($integracao['usuario_acesso'] ?? '') ?>" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="senha_acesso_<?= (int) $integracao['id'] ?>">Senha</label>
                            <input type="password" id="senha_acesso_<?= (int) $integracao['id'] ?>" name="senha_acesso" class="form-control"
                                   autocomplete="new-password" placeholder="Deixe em branco para manter a atual">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="ativo_<?= (int) $integracao['id'] ?>">Status</label>
                        <select id="ativo_<?= (int) $integracao['id'] ?>" name="ativo" class="form-control form-select2">
                            <option value="1"<?= (int) $integracao['ativo'] === 1 ? ' selected' : '' ?>>Ativa</option>
                            <option value="0"<?= (int) $integracao['ativo'] === 0 ? ' selected' : '' ?>>Inativa</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
                    <?php if ($integracao['codigo'] === 'inteegra'): ?>
                    <button type="button" class="btn-secondary btn-testar-integracao" data-codigo="inteegra">
                        <i class="bi bi-plug"></i> Testar conexão
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="card mt-3 card-limpeza-dados">
    <div class="card-header">
        <h2 class="card-title"><i class="bi bi-recycle"></i> Limpeza de dados</h2>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">
            Remova registros obsoletos ou órfãos. Para excluir um evento inativo com tudo que pertence a ele,
            basta marcar <strong>Eventos inativos (exclusão completa)</strong> — não é preciso combinar outras opções de evento ou pesquisa.
        </p>

        <div class="limpeza-toolbar mb-3">
            <button type="button" class="btn-secondary btn-sm" id="btnLimpezaSugeridas">
                <i class="bi bi-check2-square"></i> Selecionar sugeridos
            </button>
            <button type="button" class="btn-secondary btn-sm" id="btnLimpezaLimparSelecao">
                <i class="bi bi-x-square"></i> Limpar seleção
            </button>
            <button type="button" class="btn-secondary btn-sm" id="btnLimpezaAtualizar">
                <i class="bi bi-arrow-clockwise"></i> Atualizar contagens
            </button>
        </div>

        <div id="limpezaOpcoes" class="limpeza-opcoes"
             data-sugeridas="<?= h(json_encode($limpezaSugeridas)) ?>"
             data-absorvidas="<?= h(json_encode($limpezaAbsorvidas)) ?>">
            <?php
            $grupoAtual = '';
            foreach ($limpezaOpcoes as $chave => $meta):
                if ($grupoAtual !== $meta['grupo']):
                    if ($grupoAtual !== '') {
                        echo '</div>';
                    }
                    $grupoAtual = $meta['grupo'];
                    echo '<h3 class="limpeza-grupo-titulo">' . h($grupoAtual) . '</h3><div class="limpeza-grupo">';
                endif;
                $nuclear = !empty($meta['nuclear']);
            ?>
            <label class="limpeza-opcao<?= $nuclear ? ' limpeza-opcao-nuclear' : '' ?>">
                <input type="checkbox" name="limpeza_opcao[]" value="<?= h($chave) ?>" data-opcao="<?= h($chave) ?>">
                <span class="limpeza-opcao-body">
                    <span class="limpeza-opcao-top">
                        <strong class="limpeza-opcao-label"><?= h($meta['label']) ?></strong>
                        <span class="limpeza-opcao-badge" data-contagem="<?= h($chave) ?>">…</span>
                    </span>
                    <span class="limpeza-opcao-desc"><?= h($meta['descricao']) ?></span>
                </span>
            </label>
            <?php endforeach; ?>
            <?php if ($grupoAtual !== ''): ?></div><?php endif; ?>
        </div>

        <div class="limpeza-actions mt-3">
            <button type="button" class="btn-danger" id="btnLimpezaExecutar" disabled>
                <i class="bi bi-trash3"></i> Executar limpeza selecionada
            </button>
        </div>
    </div>
</div>
