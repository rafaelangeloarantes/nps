<?php
/**
 * Conteúdo da página Formulários — incluído pelo index.php no Main.
 * Usa $_SESSION (form_erros, form_success, form_data) definida no index.
 */
$pageTitle = 'Formulários';
$pageSubtitle = 'Modelo de formulário com moeda BRL, Select2 e campos por contexto.';
$formData = $_SESSION['form_data'] ?? [];
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="page-subtitle"><?php echo htmlspecialchars($pageSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</div>

<div id="alertContainer" class="alert-placeholder"></div>

<?php
if (!empty($_SESSION['form_erros'])) {
    foreach ($_SESSION['form_erros'] as $msg) {
        echo '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle-fill"></i> <span>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</span></div>';
    }
    unset($_SESSION['form_erros']);
}
if (!empty($_SESSION['form_success'])) {
    echo '<div class="alert alert-success" role="alert"><i class="bi bi-check-circle-fill"></i> <span>Dados recebidos com sucesso. (Modelo sem banco — em produção gravaria aqui.)</span></div>';
    unset($_SESSION['form_success']);
}
unset($_SESSION['form_data']);
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Cadastro de exemplo</h2>
    </div>
    <div class="card-body">
        <form action="processar-form.php" method="post" class="form-model" id="formExemplo" data-initial-pais="<?php echo htmlspecialchars($formData['pais'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-initial-estado="<?php echo htmlspecialchars($formData['estado'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-initial-cidade="<?php echo htmlspecialchars($formData['cidade'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-person"></i> Dados pessoais</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required" for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" class="form-control" placeholder="Nome completo" maxlength="200" required value="<?php echo htmlspecialchars($formData['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="email@exemplo.com" value="<?php echo htmlspecialchars($formData['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="cpf">CPF</label>
                        <input type="text" id="cpf" name="cpf" class="form-control mask-cpf" placeholder="000.000.000-00" maxlength="14" value="<?php echo htmlspecialchars($formData['cpf'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="nascimento">Data de nascimento</label>
                        <input type="text" id="nascimento" name="nascimento" class="form-control mask-data" placeholder="dd/mm/aaaa" value="<?php echo htmlspecialchars($formData['nascimento'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="telefone">Telefone</label>
                        <input type="text" id="telefone" name="telefone" class="form-control mask-telefone" placeholder="(00) 0000-0000" value="<?php echo htmlspecialchars($formData['telefone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="form-hint">Fixo: (00) 0000-0000</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="celular">Celular</label>
                        <input type="text" id="celular" name="celular" class="form-control mask-celular" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($formData['celular'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="form-hint">(00) 00000-0000</span>
                    </div>
                </div>
            </div>

            <div class="form-section form-localidade" id="sectionLocalidade">
                <h3 class="form-section-title"><i class="bi bi-geo-alt"></i> Localidade</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="pais">País</label>
                        <select id="pais" name="pais" class="form-control form-select2" data-select2 data-paises>
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div class="form-group form-group-estado">
                        <label class="form-label" for="estado">Estado / Província</label>
                        <div class="estado-brasil-wrap">
                            <select id="estado" class="form-control form-select2" data-estados-cidades>
                                <option value="">Selecione o país primeiro</option>
                            </select>
                        </div>
                        <div class="estado-outros-wrap hidden">
                            <input type="text" id="estado_text" class="form-control" placeholder="Estado, província ou região" maxlength="120" autocomplete="address-level1">
                        </div>
                    </div>
                    <div class="form-group form-group-cidade">
                        <label class="form-label" for="cidade">Cidade</label>
                        <div class="cidade-brasil-wrap">
                            <select id="cidade" class="form-control form-select2" data-cidade-uf>
                                <option value="">Selecione o estado primeiro</option>
                            </select>
                        </div>
                        <div class="cidade-outros-wrap hidden">
                            <input type="text" id="cidade_text" class="form-control" placeholder="Cidade" maxlength="120" autocomplete="address-level2">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-briefcase"></i> Dados de negócio</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="cnpj">CNPJ</label>
                        <input type="text" id="cnpj" name="cnpj" class="form-control mask-cnpj" placeholder="00.000.000/0000-00" maxlength="18" value="<?php echo htmlspecialchars($formData['cnpj'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="hora">Hora</label>
                        <input type="text" id="hora" name="hora" class="form-control mask-hora" placeholder="00:00" value="<?php echo htmlspecialchars($formData['hora'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="categoria_id">Categoria</label>
                        <select id="categoria_id" name="categoria_id" class="form-control form-select2" data-select2>
                            <option value="">Selecione...</option>
                            <?php
                            $categorias = ['Alimentos', 'Bebidas', 'Eletrônicos', 'Vestuário', 'Serviços', 'Construção', 'Saúde', 'Educação', 'Transporte', 'Outros'];
                            $catVal = isset($formData['categoria_id']) ? (int) $formData['categoria_id'] : 0;
                            foreach ($categorias as $id => $nome) {
                                $v = $id + 1;
                                $sel = ($v === $catVal) ? ' selected' : '';
                                echo '<option value="' . $v . '"' . $sel . '>' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            ?>
                        </select>
                        <span class="form-hint">Select com busca (Select2) — use em listas vindas do banco.</span>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-share"></i> Redes sociais</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="linkedin">LinkedIn</label>
                        <div class="input-social-wrap" data-social="linkedin">
                            <span class="input-social-prefix" aria-hidden="true">linkedin.com/in/</span>
                            <input type="text" id="linkedin" name="linkedin" class="form-control input-social-input" placeholder="nome-do-perfil" maxlength="100" value="<?php echo htmlspecialchars($formData['linkedin'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                        </div>
                        <span class="form-error input-social-error" id="linkedinError" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="instagram">Instagram</label>
                        <div class="input-social-wrap" data-social="instagram">
                            <span class="input-social-prefix" aria-hidden="true">instagram.com/</span>
                            <input type="text" id="instagram" name="instagram" class="form-control input-social-input" placeholder="usuario" maxlength="30" value="<?php echo htmlspecialchars($formData['instagram'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                        </div>
                        <span class="form-error input-social-error" id="instagramError" role="alert"></span>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-currency-dollar"></i> Valores</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="valor">Valor (R$)</label>
                        <input type="text" id="valor" name="valor" class="form-control currency-brl" placeholder="R$ 0,00" data-currency-brl>
                        <span class="form-hint">Padrão Brasil: R$, ponto para milhar, vírgula para decimal.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="desconto">Desconto (R$)</label>
                        <input type="text" id="desconto" name="desconto" class="form-control currency-brl" placeholder="R$ 0,00" data-currency-brl>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title"><i class="bi bi-chat-left-text"></i> Observações</h3>
                <div class="form-group">
                    <label class="form-label" for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" class="form-control" rows="4" placeholder="Texto livre, pode conter quebras de linha e caracteres especiais." data-allow-html><?php echo htmlspecialchars($formData['observacoes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <span class="form-hint textarea-hint-html">Ao gravar no banco: use prepared statements e trate HTML/caracteres especiais (ex.: htmlspecialchars na exibição ou sanitize antes de salvar).</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('formExemplo').reset();"><i class="bi bi-x-lg"></i> Limpar</button>
            </div>
        </form>
    </div>
</div>
