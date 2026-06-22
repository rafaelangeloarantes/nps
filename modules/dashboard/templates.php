<?php
/**
 * Templates de dashboard — layout reutilizável (estilo Reportei)
 */

require_once __DIR__ . '/log.php';

function dashboard_template_garantir_estrutura($conn)
{
    static $ok = false;
    if ($ok) {
        return;
    }

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS `dashboard_templates` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(255) NOT NULL,
            `descricao` TEXT NULL,
            `contrato_id` INT NULL,
            `layout_json` JSON NOT NULL,
            `criado_por` INT NULL,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            KEY `idx_dt_contrato` (`contrato_id`),
            KEY `idx_dt_ativo` (`ativo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ok = true;
}

function dashboard_tipos_grafico()
{
    return [
        'pie' => 'Pizza',
        'donut' => 'Rosca',
        'bar' => 'Barras',
        'nps' => 'NPS',
        'line' => 'Linha',
        'metric' => 'Métrica',
    ];
}

function dashboard_tipos_bloco()
{
    return [
        'contador' => 'Contador (big number)',
        'grafico' => 'Gráfico',
        'grade' => 'Grade de resultados',
    ];
}

function dashboard_tamanhos_bloco()
{
    return [
        'contador' => 'Card pequeno (~6 por linha)',
        'terco' => 'Um terço da linha (~3 gráficos)',
        'metade' => 'Metade da linha (~2 gráficos)',
        'inteiro' => 'Linha inteira',
    ];
}

function dashboard_tipos_metrica()
{
    return [
        'total' => 'Total de registros',
        'preenchidos' => 'Registros preenchidos',
        'distintos' => 'Valores distintos',
        'soma' => 'Soma (numérico)',
        'media' => 'Média (numérico)',
        'nps' => 'Score NPS',
    ];
}

function dashboard_fonte_para_categoria($fonte)
{
    $map = [
        'participante' => 'participante',
        'evento_extra' => 'evento',
        'pesquisa' => 'pesquisa',
        'credenciamento' => 'credenciamento',
    ];
    return $map[$fonte] ?? 'evento';
}

function dashboard_tamanho_para_largura($tamanho)
{
    $map = [
        'contador' => 2,
        'terco' => 4,
        'metade' => 6,
        'inteiro' => 12,
    ];
    return $map[$tamanho] ?? 6;
}

function dashboard_colunas_linha_opcoes()
{
    return [
        2 => '2 por linha',
        3 => '3 por linha',
        4 => '4 por linha',
        5 => '5 por linha',
        6 => '6 por linha',
    ];
}

function dashboard_normalizar_colunas_linha($tipo_bloco, $colunas_linha, $tamanho = '')
{
    if ($tipo_bloco === 'grade') {
        return 1;
    }

    $colunas = (int) $colunas_linha;
    if ($colunas >= 2 && $colunas <= 6) {
        return $colunas;
    }

    $map = [
        'contador' => 6,
        'terco' => 3,
        'metade' => 2,
        'inteiro' => 1,
    ];
    if (isset($map[$tamanho])) {
        return $map[$tamanho];
    }

    return $tipo_bloco === 'contador' ? 6 : 2;
}

function dashboard_colunas_linha_para_tamanho($colunas)
{
    $colunas = (int) $colunas;
    if ($colunas <= 1) {
        return 'inteiro';
    }
    if ($colunas >= 6) {
        return 'contador';
    }
    if ($colunas >= 4) {
        return 'terco';
    }
    if ($colunas === 3) {
        return 'terco';
    }
    return 'metade';
}

function dashboard_grafico_separadores()
{
    return [
        'nenhum' => 'Não separar (valor único)',
        'virgula' => 'Separar por vírgula (,)',
        'ponto_virgula' => 'Separar por ponto e vírgula (;)',
    ];
}

function dashboard_fontes_campo()
{
    return [
        'participante' => 'Participante',
        'evento_extra' => 'Atributo do evento',
        'pesquisa' => 'Campo de pesquisa',
        'credenciamento' => 'Credenciamento',
    ];
}

/**
 * Catálogo estático de campos de participante para o builder.
 */
function dashboard_campos_participante()
{
    return [
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
}

function dashboard_template_listar_opcoes($conn, $contrato_id = null)
{
    dashboard_template_garantir_estrutura($conn);

    $sql = 'SELECT id, nome FROM dashboard_templates WHERE ativo = 1';
    if ($contrato_id) {
        $cid = (int) $contrato_id;
        $sql .= " AND (contrato_id IS NULL OR contrato_id = {$cid})";
    }
    $sql .= ' ORDER BY nome ASC';

    $result = mysqli_query($conn, $sql);
    $lista = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $lista[] = $row;
    }
    return $lista;
}

function dashboard_template_buscar($conn, $id)
{
    dashboard_template_garantir_estrutura($conn);
    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }

    $stmt = mysqli_prepare($conn, 'SELECT * FROM dashboard_templates WHERE id = ? AND ativo = 1 LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($row) {
        $row['widgets'] = dashboard_template_decodificar_layout($row['layout_json'] ?? '[]');
    }
    return $row;
}

function dashboard_template_decodificar_layout($json)
{
    if (is_array($json)) {
        return dashboard_template_normalizar_widgets($json);
    }
    $decoded = json_decode((string) $json, true);
    if (!is_array($decoded)) {
        return [];
    }
    return dashboard_template_normalizar_widgets($decoded);
}

function dashboard_template_normalizar_widgets(array $widgets)
{
    $tipos_bloco = array_keys(dashboard_tipos_bloco());
    $tipos_grafico = array_keys(dashboard_tipos_grafico());
    $tipos_metrica = array_keys(dashboard_tipos_metrica());
    $tamanhos = array_keys(dashboard_tamanhos_bloco());
    $fontes = array_keys(dashboard_fontes_campo());
    $normalizados = [];

    foreach ($widgets as $i => $w) {
        if (!is_array($w)) {
            continue;
        }

        $tipo_bloco = $w['tipo_bloco'] ?? 'grafico';
        if (!in_array($tipo_bloco, $tipos_bloco, true)) {
            $tipo_bloco = 'grafico';
        }

        $fonte = $w['fonte'] ?? 'participante';
        if (!in_array($fonte, $fontes, true)) {
            $fonte = 'participante';
        }

        $campo_padrao_slug = mb_substr(trim((string) ($w['campo_padrao_slug'] ?? '')), 0, 100, 'UTF-8');
        $campo = mb_substr(trim((string) ($w['campo'] ?? '')), 0, 255, 'UTF-8');
        if ($campo_padrao_slug === '' && $campo !== '' && $tipo_bloco !== 'grade') {
            $campo_padrao_slug = $campo;
        }

        $tipo_grafico = $w['tipo_grafico'] ?? 'bar';
        if ($tipo_bloco === 'grade') {
            $tipo_grafico = 'grade';
            $campo_padrao_slug = '';
        } elseif ($tipo_bloco === 'contador') {
            $tipo_grafico = 'metric';
        } elseif (!in_array($tipo_grafico, $tipos_grafico, true)) {
            $tipo_grafico = 'bar';
        }

        $tipo_metrica = $w['tipo_metrica'] ?? 'total';
        if (!in_array($tipo_metrica, $tipos_metrica, true)) {
            $tipo_metrica = 'total';
        }

        $tamanho = $w['tamanho'] ?? '';
        if (!in_array($tamanho, $tamanhos, true)) {
            if ($tipo_bloco === 'grade') {
                $tamanho = 'inteiro';
            } elseif ($tipo_bloco === 'contador') {
                $tamanho = 'contador';
            } else {
                $largura_legacy = (int) ($w['largura'] ?? 6);
                if ($largura_legacy >= 12) {
                    $tamanho = 'inteiro';
                } elseif ($largura_legacy <= 4) {
                    $tamanho = 'terco';
                } else {
                    $tamanho = 'metade';
                }
            }
        }

        $colunas_linha = dashboard_normalizar_colunas_linha(
            $tipo_bloco,
            $w['colunas_linha'] ?? 0,
            $tamanho
        );
        $tamanho = dashboard_colunas_linha_para_tamanho($colunas_linha);
        $largura = dashboard_tamanho_para_largura($tamanho);

        $grafico_limite = (int) ($w['grafico_limite_itens'] ?? 0);
        if ($grafico_limite < 0) {
            $grafico_limite = 0;
        }
        if ($grafico_limite > 50) {
            $grafico_limite = 50;
        }

        $grafico_separador = $w['grafico_separador'] ?? 'nenhum';
        $separadores = array_keys(dashboard_grafico_separadores());
        if (!in_array($grafico_separador, $separadores, true)) {
            $grafico_separador = 'nenhum';
        }

        $normalizados[] = [
            'id' => $w['id'] ?? ('w' . ($i + 1)),
            'tipo_bloco' => $tipo_bloco,
            'fonte' => $fonte,
            'campo_padrao_slug' => $campo_padrao_slug,
            'campo' => $campo,
            'pesquisa_id' => !empty($w['pesquisa_id']) ? (int) $w['pesquisa_id'] : null,
            'tipo_grafico' => $tipo_grafico,
            'tipo_metrica' => $tipo_metrica,
            'grafico_limite_itens' => $grafico_limite,
            'grafico_separador' => $grafico_separador,
            'titulo' => mb_substr(trim((string) ($w['titulo'] ?? '')), 0, 255, 'UTF-8'),
            'colunas_linha' => $colunas_linha,
            'tamanho' => $tamanho,
            'largura' => $largura,
            'ordem' => (int) ($w['ordem'] ?? $i),
        ];
    }

    usort($normalizados, function ($a, $b) {
        return $a['ordem'] <=> $b['ordem'];
    });

    return $normalizados;
}

function dashboard_template_salvar($conn, array $dados)
{
    dashboard_template_garantir_estrutura($conn);

    $id = (int) ($dados['id'] ?? 0);
    $nome = trim($dados['nome'] ?? '');
    $descricao = trim($dados['descricao'] ?? '');
    $contrato_id = !empty($dados['contrato_id']) ? (int) $dados['contrato_id'] : null;
    $widgets = dashboard_template_normalizar_widgets($dados['widgets'] ?? []);

    if ($nome === '') {
        return ['status' => 'error', 'message' => 'Nome do template é obrigatório.'];
    }

    if (empty($widgets)) {
        return ['status' => 'error', 'message' => 'Adicione ao menos um bloco ao template.'];
    }

    $layout_json = json_encode($widgets, JSON_UNESCAPED_UNICODE);
    $criado_por = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE dashboard_templates SET nome = ?, descricao = ?, contrato_id = ?, layout_json = ? WHERE id = ? AND ativo = 1'
        );
        mysqli_stmt_bind_param($stmt, 'ssisi', $nome, $descricao, $contrato_id, $layout_json, $id);
        $msg = 'Template atualizado com sucesso.';
        $acao = 'atualizar';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO dashboard_templates (nome, descricao, contrato_id, layout_json, criado_por) VALUES (?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'ssisi', $nome, $descricao, $contrato_id, $layout_json, $criado_por);
        $msg = 'Template cadastrado com sucesso.';
        $acao = 'criar';
    }

    if (!mysqli_stmt_execute($stmt)) {
        $erro = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        return ['status' => 'error', 'message' => 'Erro ao salvar template: ' . $erro];
    }
    mysqli_stmt_close($stmt);

    $template_id = $id > 0 ? $id : (int) mysqli_insert_id($conn);
    auditoria_registrar($conn, 'dashboard_template', $acao, $template_id, [
        'nome' => $nome,
        'widgets' => count($widgets),
    ]);

    return ['status' => 'success', 'message' => $msg, 'id' => $template_id];
}

function dashboard_template_excluir($conn, $id)
{
    dashboard_template_garantir_estrutura($conn);
    $id = (int) $id;
    if ($id <= 0) {
        return ['status' => 'error', 'message' => 'ID inválido.'];
    }

    if (!dashboard_template_buscar($conn, $id)) {
        return ['status' => 'error', 'message' => 'Template não encontrado.'];
    }

    $vinculos = dashboard_template_contar_relatorios($conn, $id);
    if ($vinculos > 0) {
        $msg = $vinculos === 1
            ? 'Não é possível excluir: existe 1 relatório vinculado a este template.'
            : 'Não é possível excluir: existem ' . $vinculos . ' relatórios vinculados a este template.';
        return ['status' => 'error', 'message' => $msg];
    }

    $stmt = mysqli_prepare($conn, 'UPDATE dashboard_templates SET ativo = 0 WHERE id = ? AND ativo = 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) === 0) {
        mysqli_stmt_close($stmt);
        return ['status' => 'error', 'message' => 'Erro ao excluir template.'];
    }
    mysqli_stmt_close($stmt);

    try {
        auditoria_registrar($conn, 'dashboard_template', 'excluir', $id);
    } catch (Throwable $e) {
        error_log('auditoria template excluir: ' . $e->getMessage());
    }

    return ['status' => 'success', 'message' => 'Template excluído com sucesso.'];
}

function dashboard_template_contar_relatorios($conn, $template_id)
{
    $template_id = (int) $template_id;
    if ($template_id <= 0) {
        return 0;
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT COUNT(*) AS total FROM dashboard_relatorios WHERE template_id = ? AND ativo = 1'
    );
    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'i', $template_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return (int) ($row['total'] ?? 0);
}

/**
 * Campos disponíveis para um evento (mapeamentos + participante + credenciamento).
 */
function dashboard_campos_disponiveis_evento($conn, $evento_id)
{
    require_once __DIR__ . '/../eventos/atributos.php';
    require_once __DIR__ . '/../pesquisas/campos.php';
    require_once __DIR__ . '/../campos_padrao/functions.php';

    $evento_id = (int) $evento_id;
    $catalogo = [
        'participante' => [],
        'evento_extra' => [],
        'pesquisa' => [],
        'credenciamento' => [
            ['campo' => 'status', 'label' => 'Status (SHOW / NOSHOW)', 'tipo_grafico' => 'pie'],
        ],
    ];

    foreach (campo_padrao_listar_opcoes($conn, ['participante']) as $cp) {
        $catalogo['participante'][] = [
            'campo' => $cp['mapeia_participante'] ?: $cp['slug'],
            'label' => $cp['nome'],
            'campo_padrao_id' => (int) $cp['id'],
            'campo_padrao_slug' => $cp['slug'],
            'tipo_grafico' => $cp['tipo_grafico_sugerido'] !== 'none' ? $cp['tipo_grafico_sugerido'] : 'bar',
        ];
    }

    $atributos = evento_atributo_listar($conn, $evento_id);
    foreach ($atributos as $attr) {
        if (!(int) ($attr['importar'] ?? 0)) {
            continue;
        }
        $nome = $attr['atributo_nome'];
        $destino = $attr['campo_destino'] ?? 'extra';
        if ($destino !== 'extra' && isset(dashboard_campos_participante()[$destino])) {
            continue;
        }
        $catalogo['evento_extra'][] = [
            'campo' => $nome,
            'label' => !empty($attr['campo_padrao_nome']) ? $attr['campo_padrao_nome'] : $nome,
            'campo_padrao_id' => $attr['campo_padrao_id'] ?? null,
            'campo_padrao_slug' => $attr['campo_padrao_slug'] ?? null,
            'tipo_grafico' => $attr['tipo_grafico'] !== 'none' ? $attr['tipo_grafico'] : 'bar',
        ];
    }

    $stmt = mysqli_prepare($conn, 'SELECT id, titulo AS nome FROM pesquisas WHERE evento_id = ? AND ativo = 1 ORDER BY titulo ASC');
    mysqli_stmt_bind_param($stmt, 'i', $evento_id);
    mysqli_stmt_execute($stmt);
    $pesquisas = mysqli_stmt_get_result($stmt);
    while ($pesq = mysqli_fetch_assoc($pesquisas)) {
        $campos = pesquisa_campo_listar($conn, (int) $pesq['id']);
        foreach ($campos as $c) {
            if (!(int) ($c['importar'] ?? 0)) {
                continue;
            }
            $catalogo['pesquisa'][] = [
                'campo' => $c['campo_origem'],
                'label' => !empty($c['campo_padrao_nome'])
                    ? $c['campo_padrao_nome'] . ' (' . $pesq['nome'] . ')'
                    : (($c['campo_label'] ?: $c['campo_origem']) . ' (' . $pesq['nome'] . ')'),
                'pesquisa_id' => (int) $pesq['id'],
                'pesquisa_nome' => $pesq['nome'],
                'campo_padrao_id' => $c['campo_padrao_id'] ?? null,
                'campo_padrao_slug' => $c['campo_padrao_slug'] ?? null,
                'tipo_grafico' => $c['tipo_grafico'] !== 'none' ? $c['tipo_grafico'] : 'bar',
            ];
        }
    }
    mysqli_stmt_close($stmt);

    return $catalogo;
}

/**
 * Métricas virtuais para contadores de credenciamento e pesquisa no builder.
 */
function dashboard_catalogo_metricas_virtuais()
{
    return [
        'credenciamento' => [
            ['slug' => '_convidados', 'nome' => 'Convidados', 'tipo_dado' => 'numero', 'tipo_grafico' => 'metric', 'tipo_metrica' => 'convidados'],
            ['slug' => '_confirmados', 'nome' => 'Confirmados', 'tipo_dado' => 'numero', 'tipo_grafico' => 'metric', 'tipo_metrica' => 'confirmados'],
            ['slug' => '_show', 'nome' => 'SHOW', 'tipo_dado' => 'numero', 'tipo_grafico' => 'metric', 'tipo_metrica' => 'show'],
            ['slug' => '_noshow', 'nome' => 'NOSHOW', 'tipo_dado' => 'numero', 'tipo_grafico' => 'metric', 'tipo_metrica' => 'noshow'],
            ['slug' => '_adesao_credenciamento', 'nome' => 'Adesão', 'tipo_dado' => 'numero', 'tipo_grafico' => 'metric', 'tipo_metrica' => 'adesao_credenciamento'],
        ],
        'pesquisa' => [
            ['slug' => '_total_respostas', 'nome' => 'Total de respostas', 'tipo_dado' => 'numero', 'tipo_grafico' => 'metric', 'tipo_metrica' => 'total_respostas'],
            ['slug' => '_adesao_pesquisa', 'nome' => 'Adesão pesquisa (vs SHOW)', 'tipo_dado' => 'numero', 'tipo_grafico' => 'metric', 'tipo_metrica' => 'adesao_pesquisa'],
        ],
    ];
}

/**
 * Colunas da grade com base nos mapeamentos reais do evento.
 */
function dashboard_colunas_mapeamento_evento($conn, $evento_id, $somente_extras = false)
{
    require_once __DIR__ . '/../eventos/atributos.php';

    $colunas = [];
    $vistos = [];

    if (!$somente_extras) {
        $colunas[] = ['label' => 'Nome', 'fonte' => 'participante', 'campo' => 'nome_completo', 'pesquisa_id' => null];
        $colunas[] = ['label' => 'E-mail', 'fonte' => 'participante', 'campo' => 'email', 'pesquisa_id' => null];
        $vistos['nome_completo'] = true;
        $vistos['email'] = true;
    }

    $atributos = evento_atributo_listar($conn, (int) $evento_id);
    foreach ($atributos as $attr) {
        if ((int) ($attr['importar'] ?? 0) !== 1) {
            continue;
        }

        $destino = $attr['campo_destino'] ?? 'extra';
        $label = !empty($attr['campo_padrao_nome']) ? $attr['campo_padrao_nome'] : $attr['atributo_nome'];

        if ($destino !== 'extra') {
            if ($somente_extras) {
                continue;
            }
            if (isset($vistos[$destino])) {
                continue;
            }
            $colunas[] = [
                'label' => $label,
                'fonte' => 'participante',
                'campo' => $destino,
                'pesquisa_id' => null,
            ];
            $vistos[$destino] = true;
            continue;
        }

        $colunas[] = [
            'label' => $label,
            'fonte' => 'evento_extra',
            'campo' => $attr['atributo_nome'],
            'pesquisa_id' => null,
        ];
    }

    if (!$somente_extras) {
        $colunas[] = [
            'label' => 'Credenciamento',
            'fonte' => 'credenciamento',
            'campo' => 'status',
            'pesquisa_id' => null,
        ];
    }

    return $colunas;
}

/**
 * Catálogo de campos padrão para montar templates (sem vínculo com evento).
 */
function dashboard_catalogo_template($conn)
{
    require_once __DIR__ . '/../campos_padrao/functions.php';

    $virtuais = dashboard_catalogo_metricas_virtuais();
    $fontes = dashboard_fontes_campo();
    $catalogo = [];

    foreach ($fontes as $fonte => $label) {
        $catalogo[$fonte] = [];

        if (isset($virtuais[$fonte])) {
            $catalogo[$fonte] = array_merge($catalogo[$fonte], $virtuais[$fonte]);
        }

        $categoria = dashboard_fonte_para_categoria($fonte);
        foreach (campo_padrao_listar_opcoes($conn, [$categoria]) as $cp) {
            $tipo_grafico = $cp['tipo_grafico_sugerido'] !== 'none' ? $cp['tipo_grafico_sugerido'] : 'bar';
            $tipo_metrica = 'total';
            if ($cp['tipo_dado'] === 'nps') {
                $tipo_metrica = 'nps';
                $tipo_grafico = 'nps';
            } elseif ($cp['tipo_dado'] === 'numero') {
                $tipo_metrica = 'media';
            } elseif ($fonte === 'credenciamento') {
                $tipo_metrica = 'preenchidos';
            }

            $catalogo[$fonte][] = [
                'slug' => $cp['slug'],
                'nome' => $cp['nome'],
                'tipo_dado' => $cp['tipo_dado'],
                'tipo_grafico' => $tipo_grafico,
                'tipo_metrica' => $tipo_metrica,
            ];
        }
    }

    return $catalogo;
}

/**
 * Resolve slug do campo padrão para o campo concreto do evento na renderização.
 */
function dashboard_resolver_widget_evento($conn, $evento_id, array $widget)
{
    require_once __DIR__ . '/../eventos/atributos.php';
    require_once __DIR__ . '/../pesquisas/campos.php';
    require_once __DIR__ . '/../campos_padrao/functions.php';

    $resolved = $widget;
    $fonte = $widget['fonte'] ?? 'participante';
    $slug = trim((string) ($widget['campo_padrao_slug'] ?? ''));

    if (($widget['tipo_bloco'] ?? '') === 'grade' || $slug === '') {
        return $resolved;
    }

    $padrao = campo_padrao_buscar_por_slug($conn, $slug);
    if (!$padrao) {
        $resolved['campo'] = $widget['campo'] ?: $slug;
        return $resolved;
    }

    if ($fonte === 'participante') {
        $resolved['campo'] = !empty($padrao['mapeia_participante'])
            ? $padrao['mapeia_participante']
            : $padrao['slug'];

        $atributos = evento_atributo_listar($conn, (int) $evento_id);
        foreach ($atributos as $attr) {
            if ((int) ($attr['importar'] ?? 0) !== 1) {
                continue;
            }
            if ((int) ($attr['campo_padrao_id'] ?? 0) !== (int) $padrao['id']) {
                continue;
            }
            if (($attr['campo_destino'] ?? 'extra') === 'extra') {
                $resolved['fonte'] = 'evento_extra';
                $resolved['campo'] = $attr['atributo_nome'];
            } else {
                $resolved['fonte'] = 'participante';
                $resolved['campo'] = $attr['campo_destino'];
            }
            return $resolved;
        }
        return $resolved;
    }

    if ($fonte === 'credenciamento') {
        $resolved['campo'] = 'status';
        return $resolved;
    }

    if ($fonte === 'evento_extra') {
        $atributos = evento_atributo_listar($conn, (int) $evento_id);
        foreach ($atributos as $attr) {
            if ((int) ($attr['importar'] ?? 0) !== 1) {
                continue;
            }
            if ((int) ($attr['campo_padrao_id'] ?? 0) === (int) $padrao['id']) {
                $resolved['campo'] = $attr['atributo_nome'];
                return $resolved;
            }
        }
        $resolved['campo'] = $widget['campo'] ?: $slug;
        return $resolved;
    }

    if ($fonte === 'pesquisa') {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM pesquisas WHERE evento_id = ? AND ativo = 1');
        mysqli_stmt_bind_param($stmt, 'i', $evento_id);
        mysqli_stmt_execute($stmt);
        $pesq_result = mysqli_stmt_get_result($stmt);
        while ($pesq = mysqli_fetch_assoc($pesq_result)) {
            $campos = pesquisa_campo_listar($conn, (int) $pesq['id']);
            foreach ($campos as $c) {
                if ((int) ($c['importar'] ?? 0) !== 1) {
                    continue;
                }
                if ((int) ($c['campo_padrao_id'] ?? 0) === (int) $padrao['id']) {
                    $resolved['campo'] = $c['campo_origem'];
                    $resolved['pesquisa_id'] = (int) $pesq['id'];
                    mysqli_stmt_close($stmt);
                    return $resolved;
                }
            }
        }
        mysqli_stmt_close($stmt);
        $resolved['campo'] = $widget['campo'] ?: $slug;
        return $resolved;
    }

    return $resolved;
}

/**
 * Colunas da grade para uma fonte de dados no evento.
 */
function dashboard_colunas_grade_fonte($conn, $evento_id, $fonte)
{
    require_once __DIR__ . '/../eventos/atributos.php';
    require_once __DIR__ . '/../pesquisas/campos.php';
    require_once __DIR__ . '/../campos_padrao/functions.php';

    $evento_id = (int) $evento_id;
    $colunas = [];

    if ($fonte === 'participante') {
        return dashboard_colunas_mapeamento_evento($conn, $evento_id, false);
    }

    if ($fonte === 'credenciamento') {
        $colunas = dashboard_colunas_mapeamento_evento($conn, $evento_id, false);
        return $colunas;
    }

    if ($fonte === 'evento_extra') {
        $colunas = [
            ['label' => 'Nome', 'fonte' => 'participante', 'campo' => 'nome_completo', 'pesquisa_id' => null],
            ['label' => 'E-mail', 'fonte' => 'participante', 'campo' => 'email', 'pesquisa_id' => null],
        ];
        $atributos = evento_atributo_listar($conn, $evento_id);
        foreach ($atributos as $attr) {
            if ((int) ($attr['importar'] ?? 0) !== 1) {
                continue;
            }
            if (($attr['campo_destino'] ?? 'extra') !== 'extra') {
                continue;
            }
            $colunas[] = [
                'label' => !empty($attr['campo_padrao_nome']) ? $attr['campo_padrao_nome'] : $attr['atributo_nome'],
                'fonte' => 'evento_extra',
                'campo' => $attr['atributo_nome'],
                'pesquisa_id' => null,
            ];
        }
        return $colunas;
    }

    if ($fonte === 'pesquisa') {
        $colunas[] = [
            'label' => 'Nome',
            'fonte' => 'participante',
            'campo' => 'nome_completo',
            'pesquisa_id' => null,
        ];
        $colunas[] = [
            'label' => 'E-mail',
            'fonte' => 'participante',
            'campo' => 'email',
            'pesquisa_id' => null,
        ];
        $stmt = mysqli_prepare($conn, 'SELECT id, titulo AS nome FROM pesquisas WHERE evento_id = ? AND ativo = 1 ORDER BY titulo ASC');
        mysqli_stmt_bind_param($stmt, 'i', $evento_id);
        mysqli_stmt_execute($stmt);
        $pesq_result = mysqli_stmt_get_result($stmt);
        while ($pesq = mysqli_fetch_assoc($pesq_result)) {
            $campos = pesquisa_campo_listar($conn, (int) $pesq['id']);
            foreach ($campos as $c) {
                if ((int) ($c['importar'] ?? 0) !== 1) {
                    continue;
                }
                $colunas[] = [
                    'label' => !empty($c['campo_padrao_nome'])
                        ? $c['campo_padrao_nome'] . ' (' . $pesq['nome'] . ')'
                        : (($c['campo_label'] ?: $c['campo_origem']) . ' (' . $pesq['nome'] . ')'),
                    'fonte' => 'pesquisa',
                    'campo' => $c['campo_origem'],
                    'pesquisa_id' => (int) $pesq['id'],
                ];
            }
        }
        mysqli_stmt_close($stmt);
        return $colunas;
    }

    return $colunas;
}
