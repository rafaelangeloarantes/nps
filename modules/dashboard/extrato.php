<?php
/**
 * Extrato master consolidado (participante + credenciamento + pesquisa)
 */

require_once __DIR__ . '/render.php';
require_once __DIR__ . '/log.php';
require_once __DIR__ . '/../eventos/atributos.php';
require_once __DIR__ . '/../pesquisas/campos.php';

/**
 * Monta linhas do extrato master para um evento.
 */
function dashboard_extrato_montar_linhas($conn, $evento_id)
{
    $evento_id = (int) $evento_id;
    $participantes = dashboard_carregar_participantes_evento($conn, $evento_id);
    $respostas = dashboard_carregar_respostas_evento($conn, $evento_id);

    $colunas_participante = array_keys(dashboard_campos_participante());
    $colunas_participante[] = 'id';

    $atributos = evento_atributo_listar($conn, $evento_id);
    $cols_extra = [];
    foreach ($atributos as $attr) {
        if (!(int) ($attr['importar'] ?? 0)) {
            continue;
        }
        $destino = $attr['campo_destino'] ?? 'extra';
        if ($destino !== 'extra') {
            continue;
        }
        $cols_extra[] = [
            'nome' => $attr['atributo_nome'],
            'label' => !empty($attr['campo_padrao_nome']) ? $attr['campo_padrao_nome'] : ('Extra: ' . $attr['atributo_nome']),
            'campo_padrao_slug' => $attr['campo_padrao_slug'] ?? null,
        ];
    }

    $pesquisas_cols = [];
    $stmt = mysqli_prepare($conn, 'SELECT id, titulo AS nome FROM pesquisas WHERE evento_id = ? AND ativo = 1');
    mysqli_stmt_bind_param($stmt, 'i', $evento_id);
    mysqli_stmt_execute($stmt);
    $pesq_result = mysqli_stmt_get_result($stmt);
    while ($pesq = mysqli_fetch_assoc($pesq_result)) {
        $campos = pesquisa_campo_listar($conn, (int) $pesq['id']);
        foreach ($campos as $c) {
            if (!(int) ($c['importar'] ?? 0)) {
                continue;
            }
            $pesquisas_cols[] = [
                'pesquisa_id' => (int) $pesq['id'],
                'pesquisa_nome' => $pesq['nome'],
                'campo' => $c['campo_origem'],
                'label' => !empty($c['campo_padrao_nome'])
                    ? $c['campo_padrao_nome']
                    : ($pesq['nome'] . ' — ' . ($c['campo_label'] ?: $c['campo_origem'])),
            ];
        }
    }
    mysqli_stmt_close($stmt);

    $cabecalho = ['Participante ID', 'Nome completo', 'E-mail', 'Telefone', 'Cargo', 'Empresa', 'Estado', 'Cidade', 'Data nascimento', 'LinkedIn', 'Credenciamento'];
    foreach ($cols_extra as $extra) {
        $cabecalho[] = $extra['label'];
    }
    foreach ($pesquisas_cols as $pc) {
        $cabecalho[] = $pc['label'];
    }

    $linhas = [];
    foreach ($participantes as $p) {
        $linha = [
            (int) $p['id'],
            $p['nome_completo'],
            $p['email'],
            $p['telefone'] ?? '',
            $p['cargo'] ?? '',
            $p['empresa'] ?? '',
            $p['estado'] ?? '',
            $p['cidade'] ?? '',
            $p['data_nascimento'] ?? '',
            $p['linkedin'] ?? '',
            $p['credenciamento_status'] ?? '',
        ];

        foreach ($cols_extra as $extra) {
            $linha[] = $p['extras'][$extra['nome']] ?? '';
        }

        $pid = (int) $p['id'];
        $key = 'p' . $pid;
        if (!isset($respostas[$key])) {
            $key = 'e' . strtolower($p['email'] ?? '');
        }

        foreach ($pesquisas_cols as $pc) {
            $valor = '';
            if (isset($respostas[$key][$pc['pesquisa_id']]['campos'][$pc['campo']])) {
                $valor = $respostas[$key][$pc['pesquisa_id']]['campos'][$pc['campo']];
            }
            $linha[] = is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : $valor;
        }

        $linhas[] = $linha;
    }

    return [
        'cabecalho' => $cabecalho,
        'linhas' => $linhas,
        'total' => count($linhas),
    ];
}

function dashboard_extrato_registrar_log($conn, $relatorio_id, $evento_id, $total_linhas)
{
    auditoria_registrar($conn, 'dashboard_extrato', 'exportar_xlsx', (int) $relatorio_id, [
        'evento_id' => (int) $evento_id,
        'total_linhas' => (int) $total_linhas,
        'usuario' => $_SESSION['user_email'] ?? null,
    ]);
}
