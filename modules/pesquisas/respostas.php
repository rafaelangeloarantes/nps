<?php
/**
 * Respostas de pesquisa — vínculo com participantes (chave: e-mail + evento)
 */

require_once __DIR__ . '/campos.php';
require_once __DIR__ . '/../participantes/functions.php';

function pesquisa_resposta_garantir_estrutura($conn)
{
    static $ok = false;
    if ($ok) {
        return;
    }

    pesquisa_campo_garantir_estrutura($conn);

    $col = mysqli_query($conn, "SHOW COLUMNS FROM relatorio_pesquisa_respostas LIKE 'evento_id'");
    if ($col && mysqli_num_rows($col) === 0) {
        mysqli_query(
            $conn,
            'ALTER TABLE relatorio_pesquisa_respostas
             ADD COLUMN evento_id INT NULL AFTER pesquisa_id,
             ADD KEY idx_rpr_participante (participante_id),
             ADD KEY idx_rpr_evento (evento_id)'
        );
        mysqli_query(
            $conn,
            'UPDATE relatorio_pesquisa_respostas r
             INNER JOIN pesquisas p ON p.id = r.pesquisa_id
             SET r.evento_id = p.evento_id
             WHERE r.evento_id IS NULL'
        );
    }

    $ok = true;
}

/**
 * Resolve participante pelo e-mail, garante vínculo com o evento da pesquisa
 */
function pesquisa_resposta_resolver_participante($conn, $evento_id, $email, $nome = '', $guest_id = 0)
{
    pesquisa_resposta_garantir_estrutura($conn);

    $evento_id = (int) $evento_id;
    $email = trim(strtolower((string) $email));
    $nome = trim((string) $nome);
    $guest_id = (int) $guest_id;

    if ($email === '' || strpos($email, 'sem-email-') === 0) {
        return ['ok' => false, 'participante_id' => null, 'criado' => false, 'motivo' => 'sem_email'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'participante_id' => null, 'criado' => false, 'motivo' => 'email_invalido'];
    }

    $participante_id = participante_buscar_id_por_email($conn, $email);
    $criado = false;

    // Participante inativo (soft delete) não aparece na busca padrão — reutiliza o registro existente
    if ($participante_id <= 0) {
        $participante_id = participante_buscar_id_por_email($conn, $email, false);
        if ($participante_id > 0) {
            $stmt_reativar = mysqli_prepare($conn, 'UPDATE participantes SET ativo = 1 WHERE id = ?');
            if ($stmt_reativar) {
                mysqli_stmt_bind_param($stmt_reativar, 'i', $participante_id);
                mysqli_stmt_execute($stmt_reativar);
                mysqli_stmt_close($stmt_reativar);
            }
        }
    }

    if ($participante_id <= 0) {
        if ($nome === '') {
            $nome = explode('@', $email)[0];
        }

        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO participantes (nome_completo, email, dado_incompleto, motivo_incompleto)
             VALUES (?, ?, 0, \'ok\')'
        );
        if (!$stmt) {
            return ['ok' => false, 'participante_id' => null, 'criado' => false, 'motivo' => 'erro_insert'];
        }

        mysqli_stmt_bind_param($stmt, 'ss', $nome, $email);
        $insert_ok = false;
        try {
            $insert_ok = mysqli_stmt_execute($stmt);
        } catch (mysqli_sql_exception $e) {
            // Concorrência ou participante inativo não localizado — reutiliza e-mail existente
            if ((int) $e->getCode() !== 1062) {
                mysqli_stmt_close($stmt);
                throw $e;
            }
        }

        if (!$insert_ok) {
            $participante_id = participante_buscar_id_por_email($conn, $email, false);
            if ($participante_id > 0) {
                $stmt_reativar = mysqli_prepare($conn, 'UPDATE participantes SET ativo = 1 WHERE id = ?');
                if ($stmt_reativar) {
                    mysqli_stmt_bind_param($stmt_reativar, 'i', $participante_id);
                    mysqli_stmt_execute($stmt_reativar);
                    mysqli_stmt_close($stmt_reativar);
                }
            } else {
                mysqli_stmt_close($stmt);
                return ['ok' => false, 'participante_id' => null, 'criado' => false, 'motivo' => 'erro_insert'];
            }
        } else {
            $participante_id = (int) mysqli_insert_id($conn);
            $criado = true;
        }
        mysqli_stmt_close($stmt);
    }

    participante_garantir_vinculo_evento($conn, $participante_id, $evento_id, $guest_id);

    return [
        'ok' => true,
        'participante_id' => $participante_id,
        'criado' => $criado,
        'motivo' => 'ok',
    ];
}

/**
 * Lista respostas de pesquisas vinculadas a um participante (opcionalmente filtrado por evento)
 */
function pesquisa_resposta_listar_por_participante($conn, $participante_id, $evento_id = 0)
{
    pesquisa_resposta_garantir_estrutura($conn);

    $participante_id = (int) $participante_id;
    if ($participante_id <= 0) {
        return [];
    }

    $sql = 'SELECT r.id, r.pesquisa_id, r.evento_id, r.email_participante, r.dados_json, r.atualizado_em,
                   ps.titulo AS pesquisa_nome, e.nome AS evento_nome
            FROM relatorio_pesquisa_respostas r
            INNER JOIN pesquisas ps ON ps.id = r.pesquisa_id AND ps.ativo = 1
            INNER JOIN eventos e ON e.id = COALESCE(r.evento_id, ps.evento_id)
            WHERE r.participante_id = ?';

    if ($evento_id > 0) {
        $sql .= ' AND COALESCE(r.evento_id, ps.evento_id) = ' . (int) $evento_id;
    }

    $sql .= ' ORDER BY e.nome ASC, ps.titulo ASC, r.id DESC';

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, 'i', $participante_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $lista = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $dados = json_decode($row['dados_json'] ?? '{}', true);
        $row['campos'] = is_array($dados['campos'] ?? null) ? $dados['campos'] : [];
        unset($row['dados_json']);
        $lista[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $lista;
}

/**
 * Reconcilia participante_id das respostas de uma pesquisa (e-mail + evento)
 */
function pesquisa_resposta_reconciliar_vinculos($conn, $pesquisa_id)
{
    pesquisa_resposta_garantir_estrutura($conn);

    $pesquisa_id = (int) $pesquisa_id;
    $stmt = mysqli_prepare(
        $conn,
        'SELECT r.id, r.email_participante, r.dados_json, ps.evento_id
         FROM relatorio_pesquisa_respostas r
         INNER JOIN pesquisas ps ON ps.id = r.pesquisa_id
         WHERE r.pesquisa_id = ? AND (r.participante_id IS NULL OR r.participante_id = 0)'
    );
    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'i', $pesquisa_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $atualizados = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $dados = json_decode($row['dados_json'] ?? '{}', true);
        $bruto = is_array($dados['bruto'] ?? null) ? $dados['bruto'] : [];
        $nome = trim((string) ($bruto['Name'] ?? ''));
        $guest_id = (int) ($dados['guest_id'] ?? 0);

        $vinculo = pesquisa_resposta_resolver_participante(
            $conn,
            (int) $row['evento_id'],
            $row['email_participante'],
            $nome,
            $guest_id
        );

        if (!$vinculo['ok'] || empty($vinculo['participante_id'])) {
            continue;
        }

        $upd = mysqli_prepare(
            $conn,
            'UPDATE relatorio_pesquisa_respostas SET participante_id = ?, evento_id = ? WHERE id = ?'
        );
        if (!$upd) {
            continue;
        }

        $pid = (int) $vinculo['participante_id'];
        $eid = (int) $row['evento_id'];
        $rid = (int) $row['id'];
        mysqli_stmt_bind_param($upd, 'iii', $pid, $eid, $rid);
        if (mysqli_stmt_execute($upd)) {
            $atualizados++;
        }
        mysqli_stmt_close($upd);
    }

    mysqli_stmt_close($stmt);
    return $atualizados;
}
