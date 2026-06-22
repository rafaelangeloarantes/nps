<?php
/**
 * Funções do módulo Participantes
 */

require_once __DIR__ . '/../log/functions.php';

function participante_buscar_por_id($conn, $id)
{
    $stmt = mysqli_prepare($conn, 'SELECT * FROM participantes WHERE id = ? AND ativo = 1 LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row) {
        return null;
    }

    $row['eventos'] = participante_listar_eventos($conn, $id);
    return $row;
}

function participante_listar_eventos($conn, $participante_id)
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT e.id, e.nome FROM participante_eventos pe
         INNER JOIN eventos e ON e.id = pe.evento_id
         WHERE pe.participante_id = ? AND e.ativo = 1
         ORDER BY e.nome ASC'
    );
    mysqli_stmt_bind_param($stmt, 'i', $participante_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $lista = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $lista[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $lista;
}

function participante_validar_email($conn, $email, $id_excluir = 0)
{
    $email = trim(strtolower($email));
    if ($email === '') {
        return ['valido' => false, 'motivo' => 'sem_email'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valido' => false, 'motivo' => 'sem_email'];
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT id FROM participantes WHERE LOWER(email) = ? AND ativo = 1 AND id != ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'si', $email, $id_excluir);
    mysqli_stmt_execute($stmt);
    $dup = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($dup) {
        return ['valido' => false, 'motivo' => 'email_duplicado'];
    }

    return ['valido' => true, 'motivo' => 'ok'];
}

function participante_salvar($conn, $dados)
{
    $id = (int) ($dados['id'] ?? 0);
    $nome = trim($dados['nome_completo'] ?? '');
    $email = trim(strtolower($dados['email'] ?? ''));
    $telefone = trim($dados['telefone'] ?? '');
    $cargo = trim($dados['cargo'] ?? '');
    $empresa = trim($dados['empresa'] ?? '');
    $estado = trim($dados['estado'] ?? '');
    $cidade = trim($dados['cidade'] ?? '');
    $data_nascimento = trim($dados['data_nascimento'] ?? '') ?: null;
    $linkedin = trim($dados['linkedin'] ?? '');
    $eventos_ids = $dados['eventos_ids'] ?? [];

    if ($nome === '') {
        return ['status' => 'error', 'message' => 'Nome completo é obrigatório.'];
    }

    $validacao = participante_validar_email($conn, $email, $id);
    $dado_incompleto = $validacao['valido'] ? 0 : 1;
    $motivo_incompleto = $validacao['motivo'];

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE participantes SET nome_completo=?, email=?, telefone=?, cargo=?, empresa=?,
             estado=?, cidade=?, data_nascimento=?, linkedin=?, dado_incompleto=?, motivo_incompleto=?
             WHERE id=?'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssissi',
            $nome,
            $email,
            $telefone,
            $cargo,
            $empresa,
            $estado,
            $cidade,
            $data_nascimento,
            $linkedin,
            $dado_incompleto,
            $motivo_incompleto,
            $id
        );
        $msg = 'Participante atualizado com sucesso.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO participantes (nome_completo, email, telefone, cargo, empresa, estado, cidade,
             data_nascimento, linkedin, dado_incompleto, motivo_incompleto)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssiss',
            $nome,
            $email,
            $telefone,
            $cargo,
            $empresa,
            $estado,
            $cidade,
            $data_nascimento,
            $linkedin,
            $dado_incompleto,
            $motivo_incompleto
        );
        $msg = 'Participante cadastrado com sucesso.';
    }

    if (!mysqli_stmt_execute($stmt)) {
        $erro = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        return ['status' => 'error', 'message' => 'Erro ao salvar: ' . $erro];
    }
    mysqli_stmt_close($stmt);

    $participante_id = $id > 0 ? $id : mysqli_insert_id($conn);
    participante_sincronizar_eventos($conn, $participante_id, $eventos_ids);
    log_acao($conn, 'participantes', $id > 0 ? 'editar' : 'criar', $participante_id, ['email' => $email]);

    return ['status' => 'success', 'message' => $msg, 'id' => $participante_id];
}

function participante_sincronizar_eventos($conn, $participante_id, $eventos_ids)
{
    mysqli_query($conn, 'DELETE FROM participante_eventos WHERE participante_id = ' . (int) $participante_id);

    if (!is_array($eventos_ids)) {
        return;
    }

    $stmt = mysqli_prepare($conn, 'INSERT INTO participante_eventos (participante_id, evento_id) VALUES (?, ?)');
    foreach ($eventos_ids as $evento_id) {
        $evento_id = (int) $evento_id;
        if ($evento_id <= 0) {
            continue;
        }
        mysqli_stmt_bind_param($stmt, 'ii', $participante_id, $evento_id);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
}

function participante_buscar_id_por_email($conn, $email, $apenas_ativos = true)
{
    $email = trim(strtolower((string) $email));
    if ($email === '') {
        return 0;
    }

    $sql = 'SELECT id FROM participantes WHERE LOWER(email) = ?';
    if ($apenas_ativos) {
        $sql .= ' AND ativo = 1';
    }
    $sql .= ' LIMIT 1';

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $row ? (int) $row['id'] : 0;
}

function participante_buscar_id_por_email_evento($conn, $email, $evento_id)
{
    $email = trim(strtolower((string) $email));
    $evento_id = (int) $evento_id;
    if ($email === '' || $evento_id <= 0) {
        return 0;
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT p.id FROM participantes p
         INNER JOIN participante_eventos pe ON pe.participante_id = p.id AND pe.evento_id = ?
         WHERE LOWER(p.email) = ? AND p.ativo = 1
         LIMIT 1'
    );
    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'is', $evento_id, $email);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $row ? (int) $row['id'] : 0;
}

/**
 * Localiza participante pelo guest_id da API no evento (chave de integração)
 */
function participante_buscar_id_por_guest_evento($conn, $evento_id, $guest_id)
{
    $evento_id = (int) $evento_id;
    $guest_id = (int) $guest_id;
    if ($evento_id <= 0 || $guest_id <= 0) {
        return 0;
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT participante_id FROM participante_eventos
         WHERE evento_id = ? AND guest_id_api = ? LIMIT 1'
    );
    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'ii', $evento_id, $guest_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $row ? (int) $row['participante_id'] : 0;
}

/**
 * Slug para parte local do e-mail (sem acentos e caracteres especiais)
 */
function participante_slug_email($texto)
{
    $texto = mb_strtolower(trim((string) $texto), 'UTF-8');
    if ($texto === '') {
        return '';
    }

    if (class_exists('Normalizer')) {
        $texto = Normalizer::normalize($texto, Normalizer::FORM_D);
        $texto = preg_replace('/\p{M}/u', '', $texto);
    } else {
        $map = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];
        $texto = strtr($texto, $map);
    }

    return preg_replace('/[^a-z0-9]/', '', $texto);
}

/**
 * E-mail fake: nomesobrenome@sememail.com.br
 */
function participante_email_fake_de_nome($nome, $conn, array &$emails_reservados = [])
{
    $partes = preg_split('/\s+/u', trim((string) $nome), -1, PREG_SPLIT_NO_EMPTY);
    $local = '';
    foreach ($partes as $parte) {
        $local .= participante_slug_email($parte);
    }
    if ($local === '') {
        $local = 'semnome';
    }

    $seq = 0;
    do {
        $sufixo = $seq > 0 ? str_pad((string) $seq, 4, '0', STR_PAD_LEFT) : '';
        $email = $local . $sufixo . '@sememail.com.br';
        $seq++;
    } while (
        isset($emails_reservados[$email])
        || ($conn && participante_buscar_id_por_email($conn, $email, false) > 0)
    );

    $emails_reservados[$email] = true;
    return $email;
}

/**
 * E-mail com numeração antes do @ — rafael0001@dominio.com.br
 */
function participante_email_com_sequencia($email, $sequencia)
{
    $email = trim(strtolower((string) $email));
    $partes = explode('@', $email, 2);
    $local = $partes[0] ?? 'semnome';
    $dominio = $partes[1] ?? 'sememail.com.br';
    $sufixo = str_pad((string) max(1, (int) $sequencia), 4, '0', STR_PAD_LEFT);

    return $local . $sufixo . '@' . $dominio;
}

/**
 * Resolve e-mail na importação da API — importa 100% do público
 */
function participante_resolver_email_importacao(
    $conn,
    $email_api,
    $nome,
    $evento_id,
    $guest_id,
    $participante_id_existente,
    array &$uso_email_batch,
    array &$emails_reservados
) {
    $participante_id_existente = (int) $participante_id_existente;

    // Re-sync pelo guest_id: mantém o e-mail já vinculado a este participante
    if ($participante_id_existente > 0) {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT email, dado_incompleto, motivo_incompleto FROM participantes WHERE id = ? AND ativo = 1 LIMIT 1'
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $participante_id_existente);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if ($row && trim($row['email'] ?? '') !== '') {
                $email = trim(strtolower($row['email']));
                $emails_reservados[$email] = true;
                return [
                    'email' => $email,
                    'dado_incompleto' => (int) ($row['dado_incompleto'] ?? 0),
                    'motivo' => $row['motivo_incompleto'] ?? 'ok',
                    'tipo_ajuste' => null,
                ];
            }
        }
    }

    $email_api = trim(strtolower((string) $email_api));
    $email_valido_api = $email_api !== '' && filter_var($email_api, FILTER_VALIDATE_EMAIL);

    if (!$email_valido_api) {
        $email = participante_email_fake_de_nome($nome, $conn, $emails_reservados);
        return [
            'email' => $email,
            'dado_incompleto' => 1,
            'motivo' => 'sem_email',
            'tipo_ajuste' => 'fake',
        ];
    }

    $participante_id_existente = (int) $participante_id_existente;
    $ocupante = participante_buscar_id_por_email($conn, $email_api, false);
    $mesmo_participante = $participante_id_existente > 0 && $ocupante === $participante_id_existente;
    $duplicado_banco = $ocupante > 0 && !$mesmo_participante;
    $duplicado_lote = (int) ($uso_email_batch[$email_api] ?? 0) > 0;

    if (!$duplicado_banco && !$duplicado_lote) {
        $uso_email_batch[$email_api] = (int) ($uso_email_batch[$email_api] ?? 0) + 1;
        $emails_reservados[$email_api] = true;
        return [
            'email' => $email_api,
            'dado_incompleto' => 0,
            'motivo' => 'ok',
            'tipo_ajuste' => null,
        ];
    }

    $sequencia = (int) ($uso_email_batch[$email_api] ?? 0);
    do {
        $email = participante_email_com_sequencia($email_api, max(1, $sequencia));
        if (!isset($emails_reservados[$email]) && participante_buscar_id_por_email($conn, $email, false) <= 0) {
            $uso_email_batch[$email_api] = $sequencia + 1;
            $emails_reservados[$email] = true;
            break;
        }
        $sequencia++;
    } while (true);

    return [
        'email' => $email,
        'dado_incompleto' => 1,
        'motivo' => 'sem_email',
        'tipo_ajuste' => 'sequencial',
    ];
}

function participante_garantir_vinculo_evento($conn, $participante_id, $evento_id, $guest_id = 0)
{
    $participante_id = (int) $participante_id;
    $evento_id = (int) $evento_id;
    if ($participante_id <= 0 || $evento_id <= 0) {
        return false;
    }

    $guest_id = (int) $guest_id;
    $guest_null = $guest_id > 0 ? $guest_id : null;

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO participante_eventos (participante_id, evento_id, guest_id_api)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE guest_id_api = IFNULL(VALUES(guest_id_api), guest_id_api)'
    );
    if (!$stmt) {
        $stmt = mysqli_prepare(
            $conn,
            'INSERT IGNORE INTO participante_eventos (participante_id, evento_id) VALUES (?, ?)'
        );
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'ii', $participante_id, $evento_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    mysqli_stmt_bind_param($stmt, 'iii', $participante_id, $evento_id, $guest_null);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function participante_excluir($conn, $id)
{
    $stmt = mysqli_prepare($conn, 'UPDATE participantes SET ativo = 0 WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        log_acao($conn, 'participantes', 'excluir', $id);
    }

    return $ok
        ? ['status' => 'success', 'message' => 'Participante excluído com sucesso.']
        : ['status' => 'error', 'message' => 'Erro ao excluir participante.'];
}

function participante_recalcular_incompletos($conn)
{
    mysqli_query($conn, "UPDATE participantes SET dado_incompleto = 1, motivo_incompleto = 'sem_email'
        WHERE ativo = 1 AND (email IS NULL OR email = '' OR email NOT LIKE '%@%')");

    $sql = "UPDATE participantes p
            INNER JOIN (
                SELECT LOWER(email) AS email_lower, COUNT(*) AS total
                FROM participantes WHERE ativo = 1 AND email != ''
                GROUP BY LOWER(email) HAVING total > 1
            ) dup ON LOWER(p.email) = dup.email_lower
            SET p.dado_incompleto = 1, p.motivo_incompleto = 'email_duplicado'
            WHERE p.ativo = 1";
    mysqli_query($conn, $sql);
}
