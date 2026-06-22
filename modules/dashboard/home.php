<?php
/**
 * Dashboard — cards de eventos com visão resumida
 */

require_once __DIR__ . '/render.php';
require_once __DIR__ . '/relatorios.php';
require_once __DIR__ . '/../auth/permissoes.php';

/**
 * Lista eventos com relatório ativo para exibição em cards.
 */
function dashboard_listar_cards_eventos($conn, $contrato_id = null)
{
    dashboard_relatorio_garantir_estrutura($conn);

    $where = 'r.ativo = 1 AND e.ativo = 1';
    if ($contrato_id) {
        $where .= ' AND e.contrato_id = ' . (int) $contrato_id;
    } else {
        $where .= sql_filtro_contrato('e.contrato_id');
    }

    $sql = "SELECT e.id AS evento_id, e.nome AS evento_nome, e.data_inicio, e.data_fim,
                   e.endereco, e.clima, c.nome AS contrato_nome,
                   r.id AS relatorio_id, r.nome AS relatorio_nome
            FROM dashboard_relatorios r
            INNER JOIN eventos e ON e.id = r.evento_id
            INNER JOIN contratos c ON c.id = e.contrato_id
            INNER JOIN (
                SELECT evento_id, MAX(id) AS relatorio_id
                FROM dashboard_relatorios
                WHERE ativo = 1
                GROUP BY evento_id
            ) ult ON ult.relatorio_id = r.id
            WHERE {$where}
            ORDER BY e.data_inicio DESC, e.nome ASC";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log('dashboard_listar_cards_eventos: ' . mysqli_error($conn));
        return [];
    }

    $cards = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $evento_id = (int) $row['evento_id'];
        $metricas = dashboard_metricas_evento($conn, $evento_id);

        $cards[] = [
            'evento_id' => $evento_id,
            'evento_nome' => $row['evento_nome'],
            'contrato_nome' => $row['contrato_nome'],
            'relatorio_id' => (int) $row['relatorio_id'],
            'relatorio_nome' => $row['relatorio_nome'],
            'periodo' => dashboard_formatar_periodo_evento($row['data_inicio'], $row['data_fim']),
            'endereco' => trim((string) ($row['endereco'] ?? '')),
            'clima' => trim((string) ($row['clima'] ?? '')),
            'convidados' => $metricas['convidados'],
            'confirmados' => $metricas['confirmados'],
            'show' => $metricas['show'],
            'noshow' => $metricas['noshow'],
            'url_relatorio' => 'index.php?p=dashboard_relatorio_view&id=' . (int) $row['relatorio_id'],
        ];
    }

    return $cards;
}

/**
 * Formata horário no padrão 08h00.
 */
function dashboard_formatar_hora_evento($datetime)
{
    if (empty($datetime)) {
        return '';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    return date('H', $ts) . 'h' . date('i', $ts);
}

/**
 * Formata período do evento: quinta-feira • 11/06/2026 • 08h00 às 11h30
 */
function dashboard_formatar_periodo_evento($data_inicio, $data_fim = null)
{
    if (empty($data_inicio)) {
        return 'Data não informada';
    }

    $ts_inicio = strtotime($data_inicio);
    if ($ts_inicio === false) {
        return 'Data não informada';
    }

    $dias = [
        'domingo', 'segunda-feira', 'terça-feira', 'quarta-feira',
        'quinta-feira', 'sexta-feira', 'sábado',
    ];
    $dia = $dias[(int) date('w', $ts_inicio)] ?? '';
    $data = date('d/m/Y', $ts_inicio);
    $hora_inicio = dashboard_formatar_hora_evento($data_inicio);

    $partes = array_filter([$dia, $data]);
    $periodo = implode(' • ', $partes);

    if ($hora_inicio !== '') {
        $hora_fim = dashboard_formatar_hora_evento($data_fim);
        if ($hora_fim !== '' && $hora_fim !== $hora_inicio) {
            $periodo .= ' • ' . $hora_inicio . ' às ' . $hora_fim;
        } else {
            $periodo .= ' • ' . $hora_inicio;
        }
    }

    return $periodo;
}

/**
 * Resumo formatado dos dados do evento para exibição no relatório.
 */
function dashboard_evento_meta_resumo(array $dados)
{
    return [
        'periodo' => dashboard_formatar_periodo_evento($dados['data_inicio'] ?? '', $dados['data_fim'] ?? ''),
        'local' => trim((string) ($dados['endereco'] ?? '')),
        'clima' => trim((string) ($dados['clima'] ?? '')),
    ];
}

/**
 * Renderiza período, local e clima na mesma linha do título do relatório.
 */
function dashboard_renderizar_meta_evento(array $meta)
{
    $itens = [
        ['icone' => 'bi-calendar3', 'label' => 'Período', 'valor' => $meta['periodo'] ?? '—'],
        ['icone' => 'bi-geo-alt', 'label' => 'Local', 'valor' => ($meta['local'] ?? '') !== '' ? $meta['local'] : '—'],
        ['icone' => 'bi-cloud-sun', 'label' => 'Clima', 'valor' => ($meta['clima'] ?? '') !== '' ? $meta['clima'] : '—'],
    ];

    echo '<div class="dashboard-relatorio-meta">';
    foreach ($itens as $item) {
        echo '<span class="dashboard-relatorio-meta-item">';
        echo '<i class="bi ' . h($item['icone']) . '" aria-hidden="true"></i>';
        echo '<span class="dashboard-relatorio-meta-label">' . h($item['label']) . '</span>';
        echo '<span class="dashboard-relatorio-meta-value">' . h($item['valor']) . '</span>';
        echo '</span>';
    }
    echo '</div>';
}

/**
 * Renderiza o grid de cards de eventos.
 */
function dashboard_renderizar_cards_eventos(array $cards, array $opcoes = [])
{
    $vazio_titulo = $opcoes['vazio_titulo'] ?? 'Nenhum relatório disponível';
    $vazio_texto = $opcoes['vazio_texto'] ?? 'Quando um relatório for publicado para o seu contrato, ele aparecerá aqui.';

    if (count($cards) === 0) {
        echo '<div class="dashboard-eventos-empty">';
        echo '<div class="dashboard-eventos-empty-icon"><i class="bi bi-calendar-x"></i></div>';
        echo '<h3>' . h($vazio_titulo) . '</h3>';
        echo '<p>' . h($vazio_texto) . '</p>';
        echo '</div>';
        return;
    }

    echo '<div class="dashboard-eventos-grid">';
    foreach ($cards as $card) {
        $url = $card['url_relatorio'];
        $endereco = $card['endereco'] !== '' ? $card['endereco'] : 'Local não informado';
        $clima = $card['clima'] !== '' ? $card['clima'] : 'Clima não informado';

        echo '<a href="' . h($url) . '" class="dashboard-evento-card" aria-label="Abrir relatório ' . h($card['evento_nome']) . '">';
        echo '<div class="dashboard-evento-card-accent"></div>';
        echo '<div class="dashboard-evento-card-body">';
        echo '<div class="dashboard-evento-card-head">';
        echo '<h3 class="dashboard-evento-card-title">' . h($card['evento_nome']) . '</h3>';
        echo '<p class="dashboard-evento-card-periodo"><i class="bi bi-calendar3"></i><span>' . h($card['periodo']) . '</span></p>';
        echo '</div>';

        echo '<div class="dashboard-evento-metrics">';
        $metricas_card = [
            ['label' => 'Convidados', 'valor' => (int) $card['convidados'], 'tipo' => ''],
            ['label' => 'Confirmados', 'valor' => (int) $card['confirmados'], 'tipo' => 'confirmados'],
            ['label' => 'Show', 'valor' => (int) $card['show'], 'tipo' => 'show'],
            ['label' => 'No Show', 'valor' => (int) $card['noshow'], 'tipo' => 'noshow'],
        ];
        foreach ($metricas_card as $m) {
            $classe = $m['tipo'] !== '' ? ' is-' . $m['tipo'] : '';
            echo '<div class="dashboard-evento-stat' . $classe . '">';
            echo '<span class="dashboard-evento-stat-value">' . $m['valor'] . '</span>';
            echo '<span class="dashboard-evento-stat-label">' . h($m['label']) . '</span>';
            echo '</div>';
        }
        echo '</div>';

        echo '<div class="dashboard-evento-card-footer">';
        echo '<div class="dashboard-evento-meta">';
        echo '<span class="dashboard-evento-meta-item"><i class="bi bi-geo-alt"></i>' . h($endereco) . '</span>';
        echo '<span class="dashboard-evento-meta-item"><i class="bi bi-cloud-sun"></i>' . h($clima) . '</span>';
        echo '</div>';
        echo '<span class="dashboard-evento-card-cta">Ver relatório <i class="bi bi-arrow-right"></i></span>';
        echo '</div>';
        echo '</div>';
        echo '</a>';
    }
    echo '</div>';
}
