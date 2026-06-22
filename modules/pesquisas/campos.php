<?php
/**
 * Mapeamento de campos de pesquisa (importação via API)
 */

require_once __DIR__ . '/../campos_padrao/functions.php';

function pesquisa_campo_tipos_grafico()
{
    return [
        'none' => 'Sem gráfico',
        'pie' => 'Pizza',
        'donut' => 'Rosca',
        'bar' => 'Barras',
        'nps' => 'NPS',
        'line' => 'Linha',
        'metric' => 'Métrica',
    ];
}

function pesquisa_campo_garantir_estrutura($conn)
{
    static $ok = false;
    if ($ok) {
        return;
    }

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS `relatorio_pesquisa_campos` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `pesquisa_id` INT NOT NULL,
            `campo_origem` VARCHAR(150) NOT NULL,
            `campo_label` VARCHAR(255) NULL,
            `importar` TINYINT(1) NOT NULL DEFAULT 1,
            `tipo_grafico` ENUM('none','pie','bar','donut','nps','line','metric') NOT NULL DEFAULT 'none',
            `titulo_grafico` VARCHAR(255) NULL,
            `ordem` INT NOT NULL DEFAULT 0,
            KEY `idx_rpc_pesquisa` (`pesquisa_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS `relatorio_pesquisa_respostas` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `pesquisa_id` INT NOT NULL,
            `evento_id` INT NULL,
            `email_participante` VARCHAR(255) NOT NULL,
            `participante_id` INT NULL,
            `dados_json` JSON NULL,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY `idx_rpr_pesquisa` (`pesquisa_id`),
            KEY `idx_rpr_evento` (`evento_id`),
            KEY `idx_rpr_email` (`email_participante`),
            KEY `idx_rpr_participante` (`participante_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ok = true;
}

function pesquisa_campo_normalizar_origem($campo, $max = 150)
{
    $campo = preg_replace('/\s+/u', ' ', trim((string) $campo));
    return mb_substr($campo, 0, $max, 'UTF-8');
}

function pesquisa_campo_listar($conn, $pesquisa_id)
{
    pesquisa_campo_garantir_estrutura($conn);
    campo_padrao_garantir_estrutura($conn);

    $stmt = mysqli_prepare(
        $conn,
        'SELECT rpc.*, cp.nome AS campo_padrao_nome, cp.slug AS campo_padrao_slug
         FROM relatorio_pesquisa_campos rpc
         LEFT JOIN nps_campos_padrao cp ON cp.id = rpc.campo_padrao_id AND cp.ativo = 1
         WHERE rpc.pesquisa_id = ? ORDER BY rpc.ordem ASC, rpc.campo_origem ASC'
    );
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, 'i', $pesquisa_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $lista = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $lista[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $lista;
}

function pesquisa_campo_tem_mapeamento($conn, $pesquisa_id)
{
    $campos = pesquisa_campo_listar($conn, $pesquisa_id);
    foreach ($campos as $campo) {
        if ((int) ($campo['importar'] ?? 0) === 1) {
            return true;
        }
    }
    return false;
}

function pesquisa_campo_inserir($conn, $pesquisa_id, $origem, $label, $importar, $tipo, $titulo, $ordem, $campo_padrao_id = null)
{
    $origem = pesquisa_campo_normalizar_origem($origem);
    if ($origem === '') {
        return ['ok' => false, 'error' => 'Campo inválido.'];
    }

    $label = mb_substr(trim((string) $label), 0, 255, 'UTF-8');
    $titulo = mb_substr(trim((string) $titulo), 0, 255, 'UTF-8');
    $campo_padrao_id = $campo_padrao_id ? (int) $campo_padrao_id : null;

    $tipos = array_keys(pesquisa_campo_tipos_grafico());
    if (!in_array($tipo, $tipos, true)) {
        $tipo = 'none';
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO relatorio_pesquisa_campos
         (pesquisa_id, campo_origem, campo_label, campo_padrao_id, importar, tipo_grafico, titulo_grafico, ordem)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        return ['ok' => false, 'error' => mysqli_error($conn)];
    }

    mysqli_stmt_bind_param($stmt, 'issiissi', $pesquisa_id, $origem, $label, $campo_padrao_id, $importar, $tipo, $titulo, $ordem);
    $ok = mysqli_stmt_execute($stmt);
    $erro = $ok ? '' : mysqli_error($conn);
    mysqli_stmt_close($stmt);

    return $ok ? ['ok' => true] : ['ok' => false, 'error' => $erro];
}

function pesquisa_campo_salvar_lote($conn, $pesquisa_id, array $itens)
{
    pesquisa_campo_garantir_estrutura($conn);

    $pesquisa_id = (int) $pesquisa_id;
    if ($pesquisa_id <= 0) {
        return ['status' => 'error', 'message' => 'Pesquisa inválida.'];
    }

    if (!mysqli_query($conn, 'DELETE FROM relatorio_pesquisa_campos WHERE pesquisa_id = ' . $pesquisa_id)) {
        return ['status' => 'error', 'message' => 'Erro ao limpar mapeamento: ' . mysqli_error($conn)];
    }

    if (empty($itens)) {
        return ['status' => 'success', 'message' => 'Mapeamento salvo com sucesso.'];
    }

    foreach ($itens as $i => $item) {
        if (!is_array($item)) {
            continue;
        }

        $origem = pesquisa_campo_normalizar_origem($item['campo_origem'] ?? '');
        if ($origem === '') {
            continue;
        }

        $label = trim($item['campo_label'] ?? $origem);
        $importar = !empty($item['importar']) ? 1 : 0;
        $tipo = $item['tipo_grafico'] ?? 'none';
        $titulo = trim($item['titulo_grafico'] ?? '');
        $ordem = (int) ($item['ordem'] ?? $i);
        $campo_padrao_id = !empty($item['campo_padrao_id']) ? (int) $item['campo_padrao_id'] : null;

        if ($campo_padrao_id) {
            $padrao = campo_padrao_buscar($conn, $campo_padrao_id);
            if ($padrao) {
                if ($tipo === 'none' && ($padrao['tipo_grafico_sugerido'] ?? 'none') !== 'none') {
                    $tipo = $padrao['tipo_grafico_sugerido'];
                }
                if ($titulo === '') {
                    $titulo = $padrao['nome'];
                }
            }
        }

        $res = pesquisa_campo_inserir($conn, $pesquisa_id, $origem, $label, $importar, $tipo, $titulo, $ordem, $campo_padrao_id);
        if (!$res['ok']) {
            return ['status' => 'error', 'message' => 'Erro ao salvar campo "' . $origem . '": ' . $res['error']];
        }
    }

    return ['status' => 'success', 'message' => 'Mapeamento salvo com sucesso.'];
}

function pesquisa_campo_mesclar_descoberta($conn, $descobertos, $salvos)
{
    $mapa_salvos = [];
    foreach ($salvos as $s) {
        $mapa_salvos[$s['campo_origem']] = $s;
    }

    $resultado = [];
    foreach ($descobertos as $campo) {
        $origem = $campo['campo_origem'] ?? '';
        if ($origem === '') {
            continue;
        }

        if (isset($mapa_salvos[$origem])) {
            $row = $mapa_salvos[$origem];
            $row['exemplo_valor'] = $campo['exemplo_valor'] ?? '';
            $resultado[] = $row;
            unset($mapa_salvos[$origem]);
        } else {
            $sugerido_id = campo_padrao_sugerir_por_nome($conn, $origem, ['pesquisa', 'participante']);
            $tipo_sugerido = pesquisa_campo_sugerir_grafico($origem);
            if ($sugerido_id) {
                $padrao = campo_padrao_buscar($conn, $sugerido_id);
                if ($padrao && $padrao['tipo_grafico_sugerido'] !== 'none') {
                    $tipo_sugerido = $padrao['tipo_grafico_sugerido'];
                }
            }
            $resultado[] = [
                'campo_origem' => $origem,
                'campo_label' => $campo['campo_label'] ?? $origem,
                'campo_padrao_id' => $sugerido_id,
                'importar' => 1,
                'tipo_grafico' => $tipo_sugerido,
                'titulo_grafico' => '',
                'ordem' => 0,
                'exemplo_valor' => $campo['exemplo_valor'] ?? '',
            ];
        }
    }

    foreach ($mapa_salvos as $resto) {
        $resultado[] = $resto;
    }

    return $resultado;
}

function pesquisa_campo_sugerir_grafico($campo_origem)
{
    $nome = mb_strtolower(trim($campo_origem), 'UTF-8');
    if (strpos($nome, 'nps') !== false) {
        return 'nps';
    }
    if (strpos($nome, 'nota') !== false || strpos($nome, 'score') !== false) {
        return 'metric';
    }
    if (strpos($nome, 'coment') !== false || strpos($nome, 'texto') !== false) {
        return 'none';
    }
    return 'bar';
}
