<?php
/**
 * Funções do módulo Eventos
 */

require_once __DIR__ . '/../log/functions.php';

function evento_buscar_por_id($conn, $id)
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT e.*, c.nome AS contrato_nome
         FROM eventos e
         INNER JOIN contratos c ON c.id = e.contrato_id
         WHERE e.id = ? AND e.ativo = 1 LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

/**
 * Gera slug único para evento a partir do nome.
 */
function evento_gerar_slug($conn, $nome, $excluir_id = 0)
{
    $base = mb_strtolower(trim($nome), 'UTF-8');
    $base = preg_replace('/[^a-z0-9]+/u', '-', $base);
    $base = trim($base, '-');
    if ($base === '') {
        $base = 'evento';
    }
    $base = substr($base, 0, 80);

    $slug = $base;
    $sufixo = 1;
    while (true) {
        if ($excluir_id > 0) {
            $stmt = mysqli_prepare($conn, 'SELECT id FROM eventos WHERE slug = ? AND id != ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'si', $slug, $excluir_id);
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT id FROM eventos WHERE slug = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $slug);
        }
        mysqli_stmt_execute($stmt);
        $existe = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$existe) {
            return $slug;
        }

        $slug = $base . '-' . $sufixo;
        $sufixo++;
    }
}

/**
 * Converte datetime-local (2026-06-17T14:30) para formato MySQL.
 */
function evento_normalizar_datetime($valor)
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        return null;
    }
    $valor = str_replace('T', ' ', $valor);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $valor)) {
        $valor .= ':00';
    }
    return $valor;
}

function evento_listar_opcoes($conn, $contrato_id = null)
{
    $sql = 'SELECT e.id, e.nome, e.contrato_id, c.nome AS contrato_nome
            FROM eventos e
            INNER JOIN contratos c ON c.id = e.contrato_id
            WHERE e.ativo = 1';
    if ($contrato_id) {
        $sql .= ' AND e.contrato_id = ' . (int) $contrato_id;
    }
    $sql .= ' ORDER BY e.nome ASC';

    $result = mysqli_query($conn, $sql);
    $lista = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $lista[] = $row;
    }
    return $lista;
}

function evento_salvar($conn, $dados)
{
    $id = (int) ($dados['id'] ?? 0);
    $contrato_id = (int) ($dados['contrato_id'] ?? 0);
    $nome = trim($dados['nome'] ?? '');
    $link = trim($dados['link'] ?? '');
    $data_inicio = evento_normalizar_datetime($dados['data_inicio'] ?? '');
    $data_fim = evento_normalizar_datetime($dados['data_fim'] ?? '');
    $endereco = trim($dados['endereco'] ?? '');
    $clima = trim($dados['clima'] ?? '');
    $id_integracao = trim($dados['id_integracao'] ?? '');
    $ativo = isset($dados['ativo']) ? (int) $dados['ativo'] : 1;

    if ($nome === '' || $contrato_id <= 0) {
        return ['status' => 'error', 'message' => 'Nome e contrato são obrigatórios.'];
    }

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE eventos SET contrato_id=?, nome=?, link=?,
             data_inicio=?, data_fim=?, endereco=?, clima=?, id_integracao=?, ativo=? WHERE id=?'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'isssssssii',
            $contrato_id,
            $nome,
            $link,
            $data_inicio,
            $data_fim,
            $endereco,
            $clima,
            $id_integracao,
            $ativo,
            $id
        );
        $msg = 'Evento atualizado com sucesso.';
    } else {
        $slug = evento_gerar_slug($conn, $nome);
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO eventos (contrato_id, nome, slug, link,
             data_inicio, data_fim, endereco, clima, id_integracao, ativo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'issssssssi',
            $contrato_id,
            $nome,
            $slug,
            $link,
            $data_inicio,
            $data_fim,
            $endereco,
            $clima,
            $id_integracao,
            $ativo
        );
        $msg = 'Evento cadastrado com sucesso.';
    }

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        $novo_id = $id > 0 ? $id : mysqli_insert_id($conn);
        log_acao($conn, 'eventos', $id > 0 ? 'editar' : 'criar', $novo_id, ['nome' => $nome]);
        return ['status' => 'success', 'message' => $msg, 'id' => $novo_id];
    }

    $erro = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    return ['status' => 'error', 'message' => 'Erro ao salvar: ' . $erro];
}

function evento_excluir($conn, $id)
{
    $stmt = mysqli_prepare($conn, 'UPDATE eventos SET ativo = 0 WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        log_acao($conn, 'eventos', 'excluir', $id);
    }

    return $ok
        ? ['status' => 'success', 'message' => 'Evento excluído com sucesso.']
        : ['status' => 'error', 'message' => 'Erro ao excluir evento.'];
}

function evento_marcar_sincronizacao($conn, $id)
{
    $stmt = mysqli_prepare($conn, 'UPDATE eventos SET ultima_sincronizacao = NOW() WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
