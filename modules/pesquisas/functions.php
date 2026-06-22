<?php
/**
 * Funções do módulo Pesquisas
 */

require_once __DIR__ . '/campos.php';
require_once __DIR__ . '/respostas.php';
require_once __DIR__ . '/../log/functions.php';

function pesquisa_buscar_por_id($conn, $id)
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT p.*, p.titulo AS nome, e.nome AS evento_nome
         FROM pesquisas p
         INNER JOIN eventos e ON e.id = p.evento_id
         WHERE p.id = ? AND p.ativo = 1 LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row) {
        return null;
    }

    $row['nome'] = $row['titulo'] ?? $row['nome'] ?? '';
    $row['campos'] = pesquisa_campo_listar($conn, $id);
    return $row;
}

function pesquisa_salvar($conn, $dados)
{
    $id = (int) ($dados['id'] ?? 0);
    $evento_id = (int) ($dados['evento_id'] ?? 0);
    $nome = trim($dados['nome'] ?? '');
    $identificador = trim($dados['identificador_integracao'] ?? '');
    $ativo = isset($dados['ativo']) ? (int) $dados['ativo'] : 1;

    if ($nome === '' || $evento_id <= 0 || $identificador === '') {
        return ['status' => 'error', 'message' => 'Nome, evento e identificador de integração são obrigatórios.'];
    }

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE pesquisas SET evento_id=?, titulo=?, identificador_integracao=?, ativo=? WHERE id=?'
        );
        mysqli_stmt_bind_param($stmt, 'issii', $evento_id, $nome, $identificador, $ativo, $id);
        $msg = 'Pesquisa atualizada com sucesso.';
    } else {
        $slug = 'rel-' . time() . '-' . random_int(100, 999);
        $token = bin2hex(random_bytes(16));
        $inicio = date('Y-m-d H:i:s');
        $fim = date('Y-m-d H:i:s', strtotime('+1 year'));
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO pesquisas (evento_id, titulo, identificador_integracao, slug, token_acompanhamento, data_inicio, data_fim, ativo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'issssssi', $evento_id, $nome, $identificador, $slug, $token, $inicio, $fim, $ativo);
        $msg = 'Pesquisa cadastrada com sucesso.';
    }

    if (mysqli_stmt_execute($stmt)) {
        $pesquisa_id = $id > 0 ? $id : mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        log_acao($conn, 'pesquisas', $id > 0 ? 'editar' : 'criar', $pesquisa_id, ['titulo' => $nome]);
        return ['status' => 'success', 'message' => $msg, 'id' => $pesquisa_id];
    }

    $erro = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    return ['status' => 'error', 'message' => 'Erro ao salvar: ' . $erro];
}

function pesquisa_excluir($conn, $id)
{
    $stmt = mysqli_prepare($conn, 'UPDATE pesquisas SET ativo = 0 WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        log_acao($conn, 'pesquisas', 'excluir', $id);
    }

    return $ok
        ? ['status' => 'success', 'message' => 'Pesquisa excluída com sucesso.']
        : ['status' => 'error', 'message' => 'Erro ao excluir pesquisa.'];
}

/** @deprecated Use pesquisa_campo_salvar_lote() */
function pesquisa_salvar_campos($conn, $pesquisa_id, $campos)
{
    pesquisa_campo_salvar_lote($conn, $pesquisa_id, is_array($campos) ? $campos : []);
}
