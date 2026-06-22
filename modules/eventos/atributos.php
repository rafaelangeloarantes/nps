<?php
/**
 * Mapeamento de atributos Guests por evento
 */

require_once __DIR__ . '/../integracao/inteegra_parser.php';
require_once __DIR__ . '/../campos_padrao/functions.php';

function evento_atributo_campos_destino()
{
    return [
        'extra' => 'Extra (somente relatório)',
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

/** Categorias de campos padrão usadas no mapeamento de eventos */
function evento_atributo_categorias_padrao()
{
    return ['participante', 'evento'];
}

function evento_atributo_tipos_grafico()
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

function evento_atributo_garantir_estrutura($conn)
{
    static $ok = false;
    if ($ok) {
        return;
    }

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS `evento_atributo_mapeamento` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `evento_id` INT NOT NULL,
            `atributo_nome` VARCHAR(255) NOT NULL,
            `atributo_id_api` INT NULL,
            `campo_destino` ENUM('extra','nome_completo','email','telefone','cargo','empresa','estado','cidade','data_nascimento','linkedin') NOT NULL DEFAULT 'extra',
            `importar` TINYINT(1) NOT NULL DEFAULT 1,
            `tipo_grafico` ENUM('none','pie','bar','donut','nps','line','metric') NOT NULL DEFAULT 'none',
            `titulo_grafico` VARCHAR(255) NULL,
            `ordem` INT NOT NULL DEFAULT 0,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `idx_eam_evento_atributo` (`evento_id`, `atributo_nome`),
            KEY `idx_eam_evento` (`evento_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS `participante_plotagem` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `evento_id` INT NOT NULL,
            `campo` VARCHAR(100) NOT NULL,
            `tipo_grafico` ENUM('pie','bar','donut','nps','line','metric') NOT NULL DEFAULT 'pie',
            `titulo_grafico` VARCHAR(255) NULL,
            `ordem` INT NOT NULL DEFAULT 0,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            KEY `idx_pp_evento` (`evento_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ok = true;
}

/**
 * Normaliza nome de atributo (remove quebras de linha e limita tamanho).
 */
function evento_atributo_normalizar_nome($nome, $max = 255)
{
    return inteegra_normalizar_nome_atributo($nome, $max);
}

function evento_atributo_listar($conn, $evento_id)
{
    evento_atributo_garantir_estrutura($conn);
    campo_padrao_garantir_estrutura($conn);

    $stmt = mysqli_prepare(
        $conn,
        'SELECT eam.*, cp.nome AS campo_padrao_nome, cp.slug AS campo_padrao_slug
         FROM evento_atributo_mapeamento eam
         LEFT JOIN nps_campos_padrao cp ON cp.id = eam.campo_padrao_id AND cp.ativo = 1
         WHERE eam.evento_id = ? ORDER BY eam.ordem ASC, eam.atributo_nome ASC'
    );
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, 'i', $evento_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $lista = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $lista[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $lista;
}

function evento_atributo_inserir($conn, $evento_id, $nome, $attr_id, $destino, $importar, $tipo, $titulo, $ordem, $campo_padrao_id = null)
{
    $nome = evento_atributo_normalizar_nome($nome);
    if ($nome === '') {
        return ['ok' => false, 'error' => 'Nome do atributo inválido.'];
    }

    $titulo = mb_substr(trim((string) $titulo), 0, 255, 'UTF-8');
    $campo_padrao_id = $campo_padrao_id ? (int) $campo_padrao_id : null;

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO evento_atributo_mapeamento
         (evento_id, atributo_nome, atributo_id_api, campo_destino, campo_padrao_id, importar, tipo_grafico, titulo_grafico, ordem)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        return ['ok' => false, 'error' => mysqli_error($conn)];
    }

    $attr_id = ($attr_id !== null && $attr_id !== '') ? (int) $attr_id : 0;

    mysqli_stmt_bind_param(
        $stmt,
        'isisissii',
        $evento_id,
        $nome,
        $attr_id,
        $destino,
        $campo_padrao_id,
        $importar,
        $tipo,
        $titulo,
        $ordem
    );

    $ok = mysqli_stmt_execute($stmt);
    $erro = $ok ? '' : mysqli_error($conn);
    mysqli_stmt_close($stmt);

    return $ok ? ['ok' => true] : ['ok' => false, 'error' => $erro];
}

function evento_atributo_inserir_plotagem($conn, $evento_id, $campo, $tipo, $titulo, $ordem)
{
    $campo = mb_substr(trim($campo), 0, 100, 'UTF-8');

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO participante_plotagem (evento_id, campo, tipo_grafico, titulo_grafico, ordem, ativo)
         VALUES (?, ?, ?, ?, ?, 1)'
    );
    if (!$stmt) {
        return ['ok' => false, 'error' => mysqli_error($conn)];
    }

    mysqli_stmt_bind_param($stmt, 'isssi', $evento_id, $campo, $tipo, $titulo, $ordem);
    $ok = mysqli_stmt_execute($stmt);
    $erro = $ok ? '' : mysqli_error($conn);
    mysqli_stmt_close($stmt);

    return $ok ? ['ok' => true] : ['ok' => false, 'error' => $erro];
}

function evento_atributo_salvar_lote($conn, $evento_id, array $itens)
{
    evento_atributo_garantir_estrutura($conn);

    $evento_id = (int) $evento_id;
    if ($evento_id <= 0) {
        return ['status' => 'error', 'message' => 'Evento inválido.'];
    }

    if (!mysqli_query($conn, 'DELETE FROM evento_atributo_mapeamento WHERE evento_id = ' . $evento_id)) {
        return ['status' => 'error', 'message' => 'Erro ao limpar mapeamento: ' . mysqli_error($conn)];
    }

    if (!mysqli_query($conn, 'DELETE FROM participante_plotagem WHERE evento_id = ' . $evento_id)) {
        return ['status' => 'error', 'message' => 'Erro ao limpar plotagem: ' . mysqli_error($conn)];
    }

    if (empty($itens)) {
        return ['status' => 'success', 'message' => 'Mapeamento salvo com sucesso.'];
    }

    $campos_destino = array_keys(evento_atributo_campos_destino());
    $tipos_grafico = array_keys(evento_atributo_tipos_grafico());
    $tipos_plotagem = array_diff($tipos_grafico, ['none']);

    foreach ($itens as $i => $item) {
        if (!is_array($item)) {
            continue;
        }

        $nome = evento_atributo_normalizar_nome($item['atributo_nome'] ?? '');
        if ($nome === '') {
            continue;
        }

        $attr_id = $item['atributo_id_api'] ?? null;
        $campo_padrao_id = !empty($item['campo_padrao_id']) ? (int) $item['campo_padrao_id'] : null;
        $destino = 'extra';
        if ($campo_padrao_id) {
            $destino = campo_padrao_resolver_destino_participante($conn, $campo_padrao_id);
        } else {
            $destino = $item['campo_destino'] ?? 'extra';
        }
        if (!in_array($destino, $campos_destino, true)) {
            $destino = 'extra';
        }
        $importar = !empty($item['importar']) ? 1 : 0;
        $tipo = $item['tipo_grafico'] ?? 'none';
        $titulo = trim($item['titulo_grafico'] ?? '');

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

        if (!in_array($tipo, $tipos_grafico, true)) {
            $tipo = 'none';
        }
        $ordem = (int) ($item['ordem'] ?? $i);

        $res = evento_atributo_inserir(
            $conn,
            $evento_id,
            $nome,
            $attr_id,
            $destino,
            $importar,
            $tipo,
            $titulo,
            $ordem,
            $campo_padrao_id
        );

        if (!$res['ok']) {
            return ['status' => 'error', 'message' => 'Erro ao salvar atributo "' . $nome . '": ' . $res['error']];
        }

        if ($importar && in_array($tipo, $tipos_plotagem, true)) {
            $campo_plot = $nome;
            if ($destino !== 'extra') {
                $campo_plot = $destino;
            } elseif ($campo_padrao_id) {
                $padrao_plot = campo_padrao_buscar($conn, $campo_padrao_id);
                if ($padrao_plot) {
                    $campo_plot = $padrao_plot['slug'];
                }
            }
            $res_plot = evento_atributo_inserir_plotagem($conn, $evento_id, $campo_plot, $tipo, $titulo, $ordem);
            if (!$res_plot['ok']) {
                return ['status' => 'error', 'message' => 'Erro ao salvar gráfico "' . $nome . '": ' . $res_plot['error']];
            }
        }
    }

    return ['status' => 'success', 'message' => 'Mapeamento salvo com sucesso.'];
}

function evento_atributo_mesclar_descoberta($conn, $descobertos, $salvos)
{
    $mapa_salvos = [];
    foreach ($salvos as $s) {
        $mapa_salvos[$s['atributo_nome']] = $s;
    }

    $resultado = [];
    foreach ($descobertos as $attr) {
        $nome = $attr['atributo_nome'];
        if (isset($mapa_salvos[$nome])) {
            $row = $mapa_salvos[$nome];
            $row['exemplo_valor'] = $attr['exemplo_valor'] ?? '';
            $resultado[] = $row;
            unset($mapa_salvos[$nome]);
        } else {
            $sugerido_id = campo_padrao_sugerir_por_nome($conn, $nome, evento_atributo_categorias_padrao());
            $destino_sugerido = $sugerido_id
                ? campo_padrao_resolver_destino_participante($conn, $sugerido_id)
                : inteegra_sugerir_campo_destino($nome);
            $resultado[] = [
                'atributo_nome' => $nome,
                'atributo_id_api' => $attr['atributo_id_api'] ?? null,
                'campo_destino' => $destino_sugerido,
                'campo_padrao_id' => $sugerido_id,
                'importar' => $destino_sugerido !== 'extra' || $sugerido_id ? 1 : 0,
                'tipo_grafico' => 'none',
                'titulo_grafico' => '',
                'ordem' => 0,
                'exemplo_valor' => $attr['exemplo_valor'] ?? '',
            ];
        }
    }

    foreach ($mapa_salvos as $resto) {
        $resultado[] = $resto;
    }

    return $resultado;
}
