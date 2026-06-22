<?php
/**
 * Funções do módulo Contratos
 */

require_once __DIR__ . '/../log/functions.php';
function contrato_buscar_por_id($conn, $id)
{
    $stmt = mysqli_prepare($conn, 'SELECT * FROM contratos WHERE id = ? AND ativo = 1 LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function contrato_listar_opcoes($conn)
{
    $result = mysqli_query($conn, 'SELECT id, nome FROM contratos WHERE ativo = 1 ORDER BY nome ASC');
    $lista = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $lista[] = $row;
    }
    return $lista;
}

function contrato_salvar($conn, $dados)
{
    $id = (int) ($dados['id'] ?? 0);
    $nome = trim($dados['nome'] ?? '');
    $ativo = isset($dados['ativo']) ? (int) $dados['ativo'] : 1;

    if ($nome === '') {
        return ['status' => 'error', 'message' => 'Nome é obrigatório.'];
    }

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE contratos SET nome=?, ativo=? WHERE id=?'
        );
        mysqli_stmt_bind_param($stmt, 'sii', $nome, $ativo, $id);
        $msg = 'Contrato atualizado com sucesso.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO contratos (nome, ativo) VALUES (?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'si', $nome, $ativo);
        $msg = 'Contrato cadastrado com sucesso.';
    }

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        $novo_id = $id > 0 ? $id : mysqli_insert_id($conn);
        log_acao($conn, 'contratos', $id > 0 ? 'editar' : 'criar', $novo_id, ['nome' => $nome]);
        return ['status' => 'success', 'message' => $msg, 'id' => $novo_id];
    }
    $erro = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    return ['status' => 'error', 'message' => 'Erro ao salvar: ' . $erro];
}

function contrato_excluir($conn, $id)
{
    $check = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM eventos WHERE contrato_id = ? AND ativo = 1');
    mysqli_stmt_bind_param($check, 'i', $id);
    mysqli_stmt_execute($check);
    $total = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($check))['total'];
    mysqli_stmt_close($check);

    if ($total > 0) {
        return ['status' => 'error', 'message' => 'Não é possível excluir: existem eventos vinculados.'];
    }

    $stmt = mysqli_prepare($conn, 'UPDATE contratos SET ativo = 0 WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        log_acao($conn, 'contratos', 'excluir', $id);
    }

    return $ok        ? ['status' => 'success', 'message' => 'Contrato excluído com sucesso.']
        : ['status' => 'error', 'message' => 'Erro ao excluir contrato.'];
}
