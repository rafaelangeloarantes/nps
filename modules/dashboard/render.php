<?php
/**
 * Renderização de widgets do dashboard para um evento
 */

require_once __DIR__ . '/templates.php';
require_once __DIR__ . '/../pesquisas/campos.php';
require_once __DIR__ . '/../campos_padrao/functions.php';

/**
 * Normaliza JSON de extras do participante no evento.
 */
function dashboard_normalizar_extras_json($decoded)
{
    if (!is_array($decoded)) {
        return [];
    }
    if (isset($decoded['atributos']) && is_array($decoded['atributos'])) {
        return $decoded['atributos'];
    }
    return $decoded;
}

/**
 * Carrega participantes do evento com credenciamento e extras.
 */
function dashboard_carregar_participantes_evento($conn, $evento_id)
{
    $evento_id = (int) $evento_id;
    $sql = "SELECT p.id, p.nome_completo, p.email, p.telefone, p.cargo, p.empresa,
                   p.estado, p.cidade, p.data_nascimento, p.linkedin,
                   cr.status AS credenciamento_status,
                   pe.confirmation_status_api,
                   ped.dados_json AS extras_json
            FROM participante_eventos pe
            INNER JOIN participantes p ON p.id = pe.participante_id AND p.ativo = 1
            LEFT JOIN credenciamentos cr ON cr.participante_id = p.id AND cr.evento_id = pe.evento_id AND cr.ativo = 1
            LEFT JOIN participante_evento_dados ped ON ped.participante_id = p.id AND ped.evento_id = pe.evento_id
            WHERE pe.evento_id = ?
            ORDER BY p.nome_completo ASC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $evento_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $lista = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $decoded = [];
        if (!empty($row['extras_json'])) {
            $tmp = json_decode($row['extras_json'], true);
            if (is_array($tmp)) {
                $decoded = $tmp;
            }
        }
        $row['extras'] = dashboard_normalizar_extras_json($decoded);
        unset($row['extras_json']);
        $lista[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $lista;
}

/**
 * Carrega respostas de pesquisa indexadas por participante.
 */
function dashboard_carregar_respostas_evento($conn, $evento_id)
{
    pesquisa_campo_garantir_estrutura($conn);
    $evento_id = (int) $evento_id;

    $sql = "SELECT r.participante_id, r.pesquisa_id, r.email_participante, r.dados_json, ps.titulo AS pesquisa_nome
            FROM relatorio_pesquisa_respostas r
            INNER JOIN pesquisas ps ON ps.id = r.pesquisa_id AND ps.ativo = 1
            WHERE COALESCE(r.evento_id, ps.evento_id) = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $evento_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $mapa = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $pid = (int) ($row['participante_id'] ?? 0);
        $key = $pid > 0 ? 'p' . $pid : 'e' . strtolower($row['email_participante']);
        $dados = [];
        if (!empty($row['dados_json'])) {
            $decoded = json_decode($row['dados_json'], true);
            if (is_array($decoded)) {
                $dados = $decoded['campos'] ?? $decoded;
            }
        }
        if (!isset($mapa[$key])) {
            $mapa[$key] = [];
        }
        $mapa[$key][(int) $row['pesquisa_id']] = [
            'pesquisa_nome' => $row['pesquisa_nome'],
            'campos' => $dados,
        ];
    }
    mysqli_stmt_close($stmt);

    return $mapa;
}

/**
 * Métricas consolidadas de credenciamento e pesquisa do evento.
 */
function dashboard_metricas_evento($conn, $evento_id)
{
    $evento_id = (int) $evento_id;

    $stmt = mysqli_prepare(
        $conn,
        'SELECT COUNT(DISTINCT pe.participante_id) AS convidados
         FROM participante_eventos pe
         INNER JOIN participantes p ON p.id = pe.participante_id AND p.ativo = 1
         WHERE pe.evento_id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'i', $evento_id);
    mysqli_stmt_execute($stmt);
    $convidados = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['convidados'] ?? 0);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(DISTINCT pe.participante_id) AS confirmados
         FROM participante_eventos pe
         INNER JOIN participantes p ON p.id = pe.participante_id AND p.ativo = 1
         WHERE pe.evento_id = ? AND pe.confirmation_status_api LIKE 'CN%'"
    );
    mysqli_stmt_bind_param($stmt, 'i', $evento_id);
    mysqli_stmt_execute($stmt);
    $confirmados = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['confirmados'] ?? 0);
    mysqli_stmt_close($stmt);

    if ($confirmados <= 0 && $convidados > 0) {
        $confirmados = $convidados;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total_show
         FROM credenciamentos cr
         INNER JOIN participantes p ON p.id = cr.participante_id AND p.ativo = 1
         WHERE cr.evento_id = ? AND cr.ativo = 1 AND cr.status = 'SHOW'"
    );
    mysqli_stmt_bind_param($stmt, 'i', $evento_id);
    mysqli_stmt_execute($stmt);
    $show = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total_show'] ?? 0);
    mysqli_stmt_close($stmt);

    $noshow = max(0, $confirmados - $show);
    $adesao_credenciamento = $confirmados > 0 ? round(($show / $confirmados) * 100, 1) : 0;

    $stmt = mysqli_prepare(
        $conn,
        'SELECT COUNT(DISTINCT COALESCE(NULLIF(r.participante_id, 0), r.email_participante)) AS total_respostas
         FROM relatorio_pesquisa_respostas r
         INNER JOIN pesquisas ps ON ps.id = r.pesquisa_id AND ps.ativo = 1
         WHERE COALESCE(r.evento_id, ps.evento_id) = ?'
    );
    mysqli_stmt_bind_param($stmt, 'i', $evento_id);
    mysqli_stmt_execute($stmt);
    $total_respostas = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total_respostas'] ?? 0);
    mysqli_stmt_close($stmt);

    $adesao_pesquisa = $show > 0 ? round(($total_respostas / $show) * 100, 1) : 0;

    return [
        'convidados' => $convidados,
        'confirmados' => $confirmados,
        'show' => $show,
        'noshow' => $noshow,
        'adesao_credenciamento' => $adesao_credenciamento,
        'total_respostas' => $total_respostas,
        'adesao_pesquisa' => $adesao_pesquisa,
    ];
}

function dashboard_metrica_evento_por_tipo($conn, $evento_id, $tipo_metrica, $pesquisa_id = null)
{
    $metricas = dashboard_metricas_evento($conn, $evento_id);

    if ($pesquisa_id && in_array($tipo_metrica, ['total_respostas', 'adesao_pesquisa'], true)) {
        $pesquisa_id = (int) $pesquisa_id;
        $stmt = mysqli_prepare(
            $conn,
            'SELECT COUNT(DISTINCT COALESCE(NULLIF(r.participante_id, 0), r.email_participante)) AS total
             FROM relatorio_pesquisa_respostas r
             WHERE r.pesquisa_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'i', $pesquisa_id);
        mysqli_stmt_execute($stmt);
        $total_pesquisa = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
        mysqli_stmt_close($stmt);
        $show = $metricas['show'];
        if ($tipo_metrica === 'total_respostas') {
            return ['valor' => $total_pesquisa, 'label' => 'Respostas', 'subtitulo' => 'Pesquisa específica'];
        }
        $adesao = $show > 0 ? round(($total_pesquisa / $show) * 100, 1) : 0;
        return ['valor' => $adesao . '%', 'label' => 'Adesão pesquisa', 'subtitulo' => $total_pesquisa . ' de ' . $show . ' SHOW'];
    }

    $mapa = [
        'convidados' => ['valor' => $metricas['convidados'], 'label' => 'Convidados'],
        'confirmados' => ['valor' => $metricas['confirmados'], 'label' => 'Confirmados'],
        'show' => ['valor' => $metricas['show'], 'label' => 'SHOW'],
        'noshow' => ['valor' => $metricas['noshow'], 'label' => 'NOSHOW'],
        'adesao_credenciamento' => [
            'valor' => $metricas['adesao_credenciamento'] . '%',
            'label' => 'Adesão',
        ],
        'total_respostas' => [
            'valor' => $metricas['total_respostas'],
            'label' => 'Respostas',
            'subtitulo' => 'Total de pesquisas respondidas',
        ],
        'adesao_pesquisa' => [
            'valor' => $metricas['adesao_pesquisa'] . '%',
            'label' => 'Adesão pesquisa',
            'subtitulo' => $metricas['total_respostas'] . ' respostas de ' . $metricas['show'] . ' SHOW',
        ],
    ];

    return $mapa[$tipo_metrica] ?? ['valor' => 0, 'label' => 'Métrica'];
}

function dashboard_extrair_valor_participante(array $participante, array $respostas, array $widget)
{
    $fonte = $widget['fonte'] ?? 'participante';
    $campo = $widget['campo'] ?? '';

    if ($fonte === 'credenciamento') {
        return $participante['credenciamento_status'] ?? '';
    }

    if ($fonte === 'evento_extra') {
        return $participante['extras'][$campo] ?? '';
    }

    if ($fonte === 'pesquisa') {
        $pid = (int) $participante['id'];
        $key = 'p' . $pid;
        if (!isset($respostas[$key])) {
            $key = 'e' . strtolower($participante['email'] ?? '');
        }
        if (!isset($respostas[$key])) {
            return '';
        }
        $pesquisa_id = !empty($widget['pesquisa_id']) ? (int) $widget['pesquisa_id'] : null;
        foreach ($respostas[$key] as $p_id => $resp) {
            if ($pesquisa_id && $p_id !== $pesquisa_id) {
                continue;
            }
            if (isset($resp['campos'][$campo])) {
                return $resp['campos'][$campo];
            }
        }
        return '';
    }

    $valor = $participante[$campo] ?? '';
    if (trim((string) $valor) !== '') {
        return $valor;
    }

    $extras = $participante['extras'] ?? [];
    if (isset($extras[$campo])) {
        return $extras[$campo];
    }

    return '';
}

function dashboard_extrair_valor_coluna(array $participante, array $respostas, array $coluna)
{
    return dashboard_extrair_valor_participante($participante, $respostas, [
        'fonte' => $coluna['fonte'] ?? 'participante',
        'campo' => $coluna['campo'] ?? '',
        'pesquisa_id' => $coluna['pesquisa_id'] ?? null,
    ]);
}

/**
 * Verifica se o participante possui resposta de pesquisa no evento.
 */
function dashboard_participante_tem_pesquisa(array $participante, array $respostas, $pesquisa_id = null)
{
    $pid = (int) ($participante['id'] ?? 0);
    $key = $pid > 0 ? 'p' . $pid : null;

    if ($key === null || !isset($respostas[$key])) {
        $email = strtolower(trim((string) ($participante['email'] ?? '')));
        if ($email === '') {
            return false;
        }
        $key = 'e' . $email;
    }

    if (!isset($respostas[$key]) || !is_array($respostas[$key])) {
        return false;
    }

    if ($pesquisa_id) {
        return isset($respostas[$key][(int) $pesquisa_id]);
    }

    return count($respostas[$key]) > 0;
}

/**
 * Mantém apenas participantes com resposta de pesquisa (grade Pesquisa).
 */
function dashboard_filtrar_participantes_pesquisa(array $participantes, array $respostas, $pesquisa_id = null)
{
    $filtrados = [];
    foreach ($participantes as $p) {
        if (dashboard_participante_tem_pesquisa($p, $respostas, $pesquisa_id)) {
            $filtrados[] = $p;
        }
    }
    return $filtrados;
}

function dashboard_agregar_contagem(array $valores, $incluir_vazios = false)
{
    $contagem = [];
    foreach ($valores as $v) {
        $v = trim((string) $v);
        if ($v === '') {
            if (!$incluir_vazios) {
                continue;
            }
            $v = '(vazio)';
        }
        if (!isset($contagem[$v])) {
            $contagem[$v] = 0;
        }
        $contagem[$v]++;
    }
    arsort($contagem);
    return $contagem;
}

/**
 * Expande valores multi-valorados conforme separador configurado.
 */
function dashboard_expandir_valores_grafico(array $valores, $separador)
{
    $expandidos = [];
    foreach ($valores as $v) {
        $v = trim((string) $v);
        if ($v === '') {
            continue;
        }

        if ($separador === 'virgula') {
            $partes = preg_split('/\s*,\s*/u', $v);
        } elseif ($separador === 'ponto_virgula') {
            $partes = preg_split('/\s*;\s*/u', $v);
        } else {
            $partes = [$v];
        }

        if (!is_array($partes)) {
            $partes = [$v];
        }

        foreach ($partes as $p) {
            $p = trim((string) $p);
            if ($p !== '') {
                $expandidos[] = $p;
            }
        }
    }
    return $expandidos;
}

/**
 * Mantém os N itens mais frequentes; o restante vira "Outros".
 */
function dashboard_limitar_contagem_grafico(array $contagem, $limite)
{
    $limite = (int) $limite;
    if ($limite <= 0 || count($contagem) <= $limite) {
        return $contagem;
    }

    $total = array_sum($contagem);
    $top = array_slice($contagem, 0, $limite, true);
    $resto = array_slice($contagem, $limite, null, true);
    $outros = array_sum($resto);

    if ($outros > 0) {
        $pct = $total > 0 ? round(($outros / $total) * 100, 1) : 0;
        $top['Outros (' . $pct . '%)'] = $outros;
    }

    return $top;
}

function dashboard_agregar_contagem_grafico(array $valores, $separador = 'nenhum', $limite = 0)
{
    $expandidos = dashboard_expandir_valores_grafico($valores, $separador);
    $contagem = dashboard_agregar_contagem($expandidos, false);
    return dashboard_limitar_contagem_grafico($contagem, $limite);
}

function dashboard_calcular_nps(array $valores)
{
    $promotores = 0;
    $detratores = 0;
    $neutros = 0;
    $total = 0;

    foreach ($valores as $v) {
        if (!is_numeric($v)) {
            continue;
        }
        $n = (int) $v;
        if ($n < 0 || $n > 10) {
            continue;
        }
        $total++;
        if ($n >= 9) {
            $promotores++;
        } elseif ($n <= 6) {
            $detratores++;
        } else {
            $neutros++;
        }
    }

    $score = $total > 0 ? round((($promotores - $detratores) / $total) * 100, 1) : 0;

    return [
        'score' => $score,
        'total' => $total,
        'promotores' => $promotores,
        'neutros' => $neutros,
        'detratores' => $detratores,
        'labels' => ['Detratores (0-6)', 'Neutros (7-8)', 'Promotores (9-10)'],
        'values' => [$detratores, $neutros, $promotores],
    ];
}

function dashboard_calcular_metrica(array $valores, $tipo_metrica, $total_participantes)
{
    $tipo_metrica = $tipo_metrica ?: 'total';
    $numericos = [];
    foreach ($valores as $v) {
        if (is_numeric($v)) {
            $numericos[] = (float) $v;
        }
    }

    $preenchidos = 0;
    foreach ($valores as $v) {
        if (trim((string) $v) !== '') {
            $preenchidos++;
        }
    }

    switch ($tipo_metrica) {
        case 'preenchidos':
            return ['valor' => $preenchidos, 'label' => 'Preenchidos'];
        case 'distintos':
            $distintos = [];
            foreach ($valores as $v) {
                $k = trim((string) $v);
                if ($k !== '') {
                    $distintos[$k] = true;
                }
            }
            return ['valor' => count($distintos), 'label' => 'Valores distintos'];
        case 'soma':
            return ['valor' => count($numericos) ? round(array_sum($numericos), 1) : 0, 'label' => 'Soma'];
        case 'media':
            return ['valor' => count($numericos) ? round(array_sum($numericos) / count($numericos), 1) : 0, 'label' => 'Média'];
        case 'nps':
            $nps = dashboard_calcular_nps($valores);
            return ['valor' => $nps['score'], 'label' => 'NPS', 'subtitulo' => $nps['total'] . ' respostas'];
        case 'total':
        default:
            return ['valor' => $total_participantes, 'label' => 'Total'];
    }
}

function dashboard_metricas_evento_tipos()
{
    return [
        'convidados', 'confirmados', 'show', 'noshow', 'adesao_credenciamento',
        'total_respostas', 'adesao_pesquisa',
    ];
}

function dashboard_payload_base(array $widget)
{
    $colunas = (int) ($widget['colunas_linha'] ?? 0);
    if ($colunas < 1) {
        $colunas = dashboard_normalizar_colunas_linha(
            $widget['tipo_bloco'] ?? 'grafico',
            0,
            $widget['tamanho'] ?? ''
        );
    }

    return [
        'id' => $widget['id'],
        'tipo_bloco' => $widget['tipo_bloco'] ?? 'grafico',
        'titulo' => $widget['titulo'] ?: ($widget['campo_padrao_slug'] ?: 'Widget'),
        'tamanho' => $widget['tamanho'] ?? 'metade',
        'colunas_linha' => $colunas,
        'largura' => (int) ($widget['largura'] ?? 6),
        'fonte' => $widget['fonte'] ?? '',
    ];
}

function dashboard_renderizar_widget($conn, $evento_id, array $widget, array $participantes, array $respostas)
{
    $tipo_bloco = $widget['tipo_bloco'] ?? 'grafico';
    $tipo_metrica = $widget['tipo_metrica'] ?? 'total';
    $slug = trim((string) ($widget['campo_padrao_slug'] ?? ''));

    if ($tipo_bloco !== 'grade' && strpos($slug, '_') === 0) {
        $tipo_metrica = ltrim($slug, '_');
    }

    if ($tipo_bloco === 'contador' && in_array($tipo_metrica, dashboard_metricas_evento_tipos(), true)) {
        $metrica = dashboard_metrica_evento_por_tipo(
            $conn,
            $evento_id,
            $tipo_metrica,
            $widget['pesquisa_id'] ?? null
        );
        $base = dashboard_payload_base($widget);
        $base['titulo'] = $metrica['label'] ?? ($widget['titulo'] ?: 'Contador');
        $base['tipo_bloco'] = 'contador';
        $base['dados'] = $metrica;
        return $base;
    }

    $widget = dashboard_resolver_widget_evento($conn, $evento_id, $widget);
    $titulo = $widget['titulo'] ?: ($widget['campo_padrao_slug'] ?: ($widget['campo'] ?: 'Widget'));
    $tipo_grafico = $widget['tipo_grafico'] ?? 'bar';
    $total_participantes = count($participantes);

    $padrao = $slug !== '' ? campo_padrao_buscar_por_slug($conn, $slug) : null;
    if ($padrao && ($padrao['tipo_dado'] ?? '') === 'nps') {
        $tipo_grafico = 'nps';
    } elseif ($padrao && ($padrao['tipo_grafico_sugerido'] ?? '') === 'nps') {
        $tipo_grafico = 'nps';
    }

    $valores = [];
    foreach ($participantes as $p) {
        $valores[] = dashboard_extrair_valor_participante($p, $respostas, $widget);
    }

    $base = dashboard_payload_base($widget);
    $base['titulo'] = $titulo;

    if ($tipo_bloco === 'grade') {
        $fonte_grade = $widget['fonte'] ?? 'participante';
        $colunas_def = dashboard_colunas_grade_fonte($conn, $evento_id, $fonte_grade);
        $labels = array_column($colunas_def, 'label');
        $linhas = [];

        $participantes_grade = $participantes;
        if ($fonte_grade === 'pesquisa') {
            $participantes_grade = dashboard_filtrar_participantes_pesquisa($participantes, $respostas);
        }

        foreach ($participantes_grade as $p) {
            $linha = [];
            foreach ($colunas_def as $col) {
                $valor = dashboard_extrair_valor_coluna($p, $respostas, $col);
                $linha[] = is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : $valor;
            }
            $linhas[] = $linha;
        }

        $base['tipo_bloco'] = 'grade';
        $base['dados'] = [
            'colunas' => $labels,
            'linhas' => $linhas,
        ];
        return $base;
    }

    if ($tipo_bloco === 'contador') {
        if (in_array($tipo_metrica, dashboard_metricas_evento_tipos(), true)) {
            $metrica = dashboard_metrica_evento_por_tipo($conn, $evento_id, $tipo_metrica, $widget['pesquisa_id'] ?? null);
        } else {
            $metrica = dashboard_calcular_metrica($valores, $tipo_metrica, $total_participantes);
        }
        $base['tipo_bloco'] = 'contador';
        $base['dados'] = $metrica;
        return $base;
    }

    if ($tipo_grafico === 'nps') {
        $nps = dashboard_calcular_nps($valores);
        $base['tipo_bloco'] = 'grafico';
        $base['tipo_grafico'] = 'nps';
        $base['dados'] = $nps;
        return $base;
    }

    if ($tipo_grafico === 'metric') {
        $metrica = dashboard_calcular_metrica($valores, 'media', $total_participantes);
        $base['tipo_bloco'] = 'grafico';
        $base['tipo_grafico'] = 'metric';
        $base['dados'] = $metrica;
        return $base;
    }

    $contagem = dashboard_agregar_contagem_grafico(
        $valores,
        $widget['grafico_separador'] ?? 'nenhum',
        (int) ($widget['grafico_limite_itens'] ?? 0)
    );
    if (empty($contagem)) {
        $contagem = ['Sem dados preenchidos' => 0];
    }

    $base['tipo_bloco'] = 'grafico';
    $base['tipo_grafico'] = in_array($tipo_grafico, ['pie', 'donut', 'bar', 'line'], true) ? $tipo_grafico : 'bar';
    $base['dados'] = [
        'labels' => array_keys($contagem),
        'values' => array_values($contagem),
    ];
    return $base;
}

function dashboard_renderizar_relatorio($conn, $evento_id, array $widgets)
{
    $participantes = dashboard_carregar_participantes_evento($conn, $evento_id);
    $respostas = dashboard_carregar_respostas_evento($conn, $evento_id);

    $renderizados = [];
    foreach ($widgets as $widget) {
        $renderizados[] = dashboard_renderizar_widget($conn, $evento_id, $widget, $participantes, $respostas);
    }

    return [
        'total_participantes' => count($participantes),
        'widgets' => $renderizados,
    ];
}
