<?php
/**
 * Campos Padrão NPS — catálogo central para De/Para nas importações
 */

require_once __DIR__ . '/../log/functions.php';

function campo_padrao_categorias()
{
    return [
        'participante' => 'Participante',
        'evento' => 'Evento / atributo extra',
        'pesquisa' => 'Pesquisa',
        'credenciamento' => 'Credenciamento',
    ];
}

function campo_padrao_tipos_dado()
{
    return [
        'texto' => 'Texto',
        'numero' => 'Número',
        'data' => 'Data',
        'nps' => 'NPS (0-10)',
        'email' => 'E-mail',
        'telefone' => 'Telefone',
    ];
}

function campo_padrao_tipos_grafico()
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

function campo_padrao_gerar_slug($nome, $conn, $excluir_id = 0)
{
    $slug = mb_strtolower(trim((string) $nome), 'UTF-8');
    $slug = preg_replace('/[^a-z0-9]+/u', '_', $slug);
    $slug = trim($slug, '_');
    if ($slug === '') {
        $slug = 'campo';
    }
    $base = $slug;
    $i = 1;
    while (campo_padrao_slug_em_uso($conn, $slug, $excluir_id)) {
        $slug = $base . '_' . $i;
        $i++;
    }
    return mb_substr($slug, 0, 100, 'UTF-8');
}

function campo_padrao_slug_em_uso($conn, $slug, $excluir_id = 0)
{
    campo_padrao_garantir_estrutura($conn);
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id FROM nps_campos_padrao WHERE slug = ? AND ativo = 1 AND id != ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'si', $slug, $excluir_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return (bool) $row;
}

function campo_padrao_garantir_estrutura($conn)
{
    static $ok = false;
    if ($ok) {
        return;
    }

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS `nps_campos_padrao` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(100) NOT NULL,
            `categoria` ENUM('participante','evento','pesquisa','credenciamento') NOT NULL DEFAULT 'evento',
            `tipo_dado` ENUM('texto','numero','data','nps','email','telefone') NOT NULL DEFAULT 'texto',
            `tipo_grafico_sugerido` ENUM('none','pie','bar','donut','nps','line','metric') NOT NULL DEFAULT 'bar',
            `mapeia_participante` VARCHAR(100) NULL,
            `sistema` TINYINT(1) NOT NULL DEFAULT 0,
            `ordem` INT NOT NULL DEFAULT 0,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE KEY `idx_ncp_slug` (`slug`),
            KEY `idx_ncp_categoria` (`categoria`),
            KEY `idx_ncp_ativo` (`ativo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $col = mysqli_query($conn, "SHOW COLUMNS FROM evento_atributo_mapeamento LIKE 'campo_padrao_id'");
    if ($col && mysqli_num_rows($col) === 0) {
        mysqli_query(
            $conn,
            'ALTER TABLE evento_atributo_mapeamento
             ADD COLUMN campo_padrao_id INT NULL AFTER campo_destino,
             ADD KEY idx_eam_campo_padrao (campo_padrao_id)'
        );
    }

    $col2 = mysqli_query($conn, "SHOW COLUMNS FROM relatorio_pesquisa_campos LIKE 'campo_padrao_id'");
    if ($col2 && mysqli_num_rows($col2) === 0) {
        mysqli_query(
            $conn,
            'ALTER TABLE relatorio_pesquisa_campos
             ADD COLUMN campo_padrao_id INT NULL AFTER campo_label,
             ADD KEY idx_rpc_campo_padrao (campo_padrao_id)'
        );
    }

    $ok = true;
}

function campo_padrao_seeds_definicao()
{
    return [
        ['nome' => 'Nome completo', 'slug' => 'nome_completo', 'categoria' => 'participante', 'tipo_dado' => 'texto', 'tipo_grafico_sugerido' => 'none', 'mapeia_participante' => 'nome_completo', 'ordem' => 1],
        ['nome' => 'E-mail', 'slug' => 'email', 'categoria' => 'participante', 'tipo_dado' => 'email', 'tipo_grafico_sugerido' => 'none', 'mapeia_participante' => 'email', 'ordem' => 2],
        ['nome' => 'Telefone', 'slug' => 'telefone', 'categoria' => 'participante', 'tipo_dado' => 'telefone', 'tipo_grafico_sugerido' => 'none', 'mapeia_participante' => 'telefone', 'ordem' => 3],
        ['nome' => 'Cargo', 'slug' => 'cargo', 'categoria' => 'participante', 'tipo_dado' => 'texto', 'tipo_grafico_sugerido' => 'bar', 'mapeia_participante' => 'cargo', 'ordem' => 4],
        ['nome' => 'Empresa', 'slug' => 'empresa', 'categoria' => 'participante', 'tipo_dado' => 'texto', 'tipo_grafico_sugerido' => 'bar', 'mapeia_participante' => 'empresa', 'ordem' => 5],
        ['nome' => 'Estado', 'slug' => 'estado', 'categoria' => 'participante', 'tipo_dado' => 'texto', 'tipo_grafico_sugerido' => 'bar', 'mapeia_participante' => 'estado', 'ordem' => 6],
        ['nome' => 'Cidade', 'slug' => 'cidade', 'categoria' => 'participante', 'tipo_dado' => 'texto', 'tipo_grafico_sugerido' => 'bar', 'mapeia_participante' => 'cidade', 'ordem' => 7],
        ['nome' => 'Data de nascimento', 'slug' => 'data_nascimento', 'categoria' => 'participante', 'tipo_dado' => 'data', 'tipo_grafico_sugerido' => 'none', 'mapeia_participante' => 'data_nascimento', 'ordem' => 8],
        ['nome' => 'LinkedIn', 'slug' => 'linkedin', 'categoria' => 'participante', 'tipo_dado' => 'texto', 'tipo_grafico_sugerido' => 'none', 'mapeia_participante' => 'linkedin', 'ordem' => 9],
        ['nome' => 'Status credenciamento', 'slug' => 'credenciamento_status', 'categoria' => 'credenciamento', 'tipo_dado' => 'texto', 'tipo_grafico_sugerido' => 'pie', 'mapeia_participante' => null, 'ordem' => 10],
        ['nome' => 'NPS', 'slug' => 'nps', 'categoria' => 'pesquisa', 'tipo_dado' => 'nps', 'tipo_grafico_sugerido' => 'nps', 'mapeia_participante' => null, 'ordem' => 11],
        ['nome' => 'Nota / Score', 'slug' => 'nota_score', 'categoria' => 'pesquisa', 'tipo_dado' => 'numero', 'tipo_grafico_sugerido' => 'metric', 'mapeia_participante' => null, 'ordem' => 12],
        ['nome' => 'Comentário', 'slug' => 'comentario', 'categoria' => 'pesquisa', 'tipo_dado' => 'texto', 'tipo_grafico_sugerido' => 'none', 'mapeia_participante' => null, 'ordem' => 13],
    ];
}

function campo_padrao_instalar_seeds($conn)
{
    campo_padrao_garantir_estrutura($conn);
    $count = 0;
    foreach (campo_padrao_seeds_definicao() as $seed) {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM nps_campos_padrao WHERE slug = ? LIMIT 1');
        $slug = $seed['slug'];
        mysqli_stmt_bind_param($stmt, 's', $slug);
        mysqli_stmt_execute($stmt);
        $existe = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($existe) {
            continue;
        }

        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO nps_campos_padrao (nome, slug, categoria, tipo_dado, tipo_grafico_sugerido, mapeia_participante, sistema, ordem)
             VALUES (?, ?, ?, ?, ?, ?, 1, ?)'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssi',
            $seed['nome'],
            $seed['slug'],
            $seed['categoria'],
            $seed['tipo_dado'],
            $seed['tipo_grafico_sugerido'],
            $seed['mapeia_participante'],
            $seed['ordem']
        );
        if (mysqli_stmt_execute($stmt)) {
            $count++;
        }
        mysqli_stmt_close($stmt);
    }
    return $count;
}

function campo_padrao_migrar_mapeamentos_existentes($conn)
{
    campo_padrao_garantir_estrutura($conn);
    campo_padrao_instalar_seeds($conn);

    $total = 0;
    $result = mysqli_query(
        $conn,
        "UPDATE evento_atributo_mapeamento eam
         INNER JOIN nps_campos_padrao cp ON cp.mapeia_participante = eam.campo_destino AND cp.ativo = 1
         SET eam.campo_padrao_id = cp.id
         WHERE eam.campo_destino != 'extra' AND (eam.campo_padrao_id IS NULL OR eam.campo_padrao_id = 0)"
    );
    if ($result) {
        $total += mysqli_affected_rows($conn);
    }

    return $total;
}

function campo_padrao_buscar($conn, $id)
{
    campo_padrao_garantir_estrutura($conn);
    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }
    $stmt = mysqli_prepare($conn, 'SELECT * FROM nps_campos_padrao WHERE id = ? AND ativo = 1 LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function campo_padrao_buscar_por_slug($conn, $slug)
{
    campo_padrao_garantir_estrutura($conn);
    $slug = trim((string) $slug);
    $stmt = mysqli_prepare($conn, 'SELECT * FROM nps_campos_padrao WHERE slug = ? AND ativo = 1 LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $slug);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function campo_padrao_listar_opcoes($conn, $categorias = null)
{
    campo_padrao_garantir_estrutura($conn);
    campo_padrao_instalar_seeds($conn);

    $sql = 'SELECT id, nome, slug, categoria, tipo_dado, tipo_grafico_sugerido, mapeia_participante
            FROM nps_campos_padrao WHERE ativo = 1';
    if (is_array($categorias) && !empty($categorias)) {
        $cats = array_map(function ($c) {
            return "'" . addslashes($c) . "'";
        }, $categorias);
        $sql .= ' AND categoria IN (' . implode(',', $cats) . ')';
    }
    $sql .= ' ORDER BY ordem ASC, nome ASC';

    $result = mysqli_query($conn, $sql);
    $lista = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $lista[] = $row;
    }
    return $lista;
}

function campo_padrao_resolver_destino_participante($conn, $campo_padrao_id)
{
    $padrao = campo_padrao_buscar($conn, (int) $campo_padrao_id);
    if (!$padrao || empty($padrao['mapeia_participante'])) {
        return 'extra';
    }
    $colunas = ['nome_completo', 'email', 'telefone', 'cargo', 'empresa', 'estado', 'cidade', 'data_nascimento', 'linkedin'];
    $map = $padrao['mapeia_participante'];
    return in_array($map, $colunas, true) ? $map : 'extra';
}

function campo_padrao_sugerir_por_nome($conn, $nome_origem, $categorias = null)
{
    campo_padrao_garantir_estrutura($conn);
    require_once __DIR__ . '/../integracao/inteegra_parser.php';

    $nome = mb_strtolower(trim((string) $nome_origem), 'UTF-8');
    if ($nome === '') {
        return null;
    }

    $destino_legacy = inteegra_sugerir_campo_destino($nome_origem);
    if ($destino_legacy !== 'extra') {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT id FROM nps_campos_padrao WHERE mapeia_participante = ? AND ativo = 1 LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 's', $destino_legacy);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($row) {
            return (int) $row['id'];
        }
    }

    $opcoes = campo_padrao_listar_opcoes($conn, $categorias);
    foreach ($opcoes as $op) {
        $slug = mb_strtolower($op['slug'], 'UTF-8');
        $nome_padrao = mb_strtolower($op['nome'], 'UTF-8');
        if ($nome === $slug || $nome === $nome_padrao) {
            return (int) $op['id'];
        }
    }

    if (strpos($nome, 'nps') !== false) {
        $nps = campo_padrao_buscar_por_slug($conn, 'nps');
        return $nps ? (int) $nps['id'] : null;
    }
    if (strpos($nome, 'uf') !== false || strpos($nome, 'state') !== false) {
        $estado = campo_padrao_buscar_por_slug($conn, 'estado');
        return $estado ? (int) $estado['id'] : null;
    }

    return null;
}

function campo_padrao_salvar($conn, array $dados)
{
    campo_padrao_garantir_estrutura($conn);

    $id = (int) ($dados['id'] ?? 0);
    $nome = trim($dados['nome'] ?? '');
    $slug = trim($dados['slug'] ?? '');
    $categoria = $dados['categoria'] ?? 'evento';
    $tipo_dado = $dados['tipo_dado'] ?? 'texto';
    $tipo_grafico = $dados['tipo_grafico_sugerido'] ?? 'bar';
    $mapeia = trim($dados['mapeia_participante'] ?? '') ?: null;
    $ordem = (int) ($dados['ordem'] ?? 0);

    if ($nome === '') {
        return ['status' => 'error', 'message' => 'Nome é obrigatório.'];
    }

    $cats = array_keys(campo_padrao_categorias());
    if (!in_array($categoria, $cats, true)) {
        $categoria = 'evento';
    }
    $tipos_dado = array_keys(campo_padrao_tipos_dado());
    if (!in_array($tipo_dado, $tipos_dado, true)) {
        $tipo_dado = 'texto';
    }
    $tipos_graf = array_keys(campo_padrao_tipos_grafico());
    if (!in_array($tipo_grafico, $tipos_graf, true)) {
        $tipo_grafico = 'bar';
    }

    if ($slug === '') {
        $slug = campo_padrao_gerar_slug($nome, $conn, $id);
    } else {
        $slug = campo_padrao_gerar_slug($slug, $conn, $id);
    }

    if (campo_padrao_slug_em_uso($conn, $slug, $id)) {
        return ['status' => 'error', 'message' => 'Slug já em uso por outro campo padrão.'];
    }

    if ($id > 0) {
        $atual = campo_padrao_buscar($conn, $id);
        if (!$atual) {
            return ['status' => 'error', 'message' => 'Campo não encontrado.'];
        }
        if ((int) $atual['sistema'] === 1 && $slug !== $atual['slug']) {
            return ['status' => 'error', 'message' => 'Campos de sistema não podem ter o slug alterado.'];
        }
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE nps_campos_padrao SET nome=?, slug=?, categoria=?, tipo_dado=?, tipo_grafico_sugerido=?, mapeia_participante=?, ordem=? WHERE id=?'
        );
        mysqli_stmt_bind_param($stmt, 'ssssssii', $nome, $slug, $categoria, $tipo_dado, $tipo_grafico, $mapeia, $ordem, $id);
        $msg = 'Campo padrão atualizado com sucesso.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO nps_campos_padrao (nome, slug, categoria, tipo_dado, tipo_grafico_sugerido, mapeia_participante, ordem)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'ssssssi', $nome, $slug, $categoria, $tipo_dado, $tipo_grafico, $mapeia, $ordem);
        $msg = 'Campo padrão cadastrado com sucesso.';
    }

    if (!mysqli_stmt_execute($stmt)) {
        $erro = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        return ['status' => 'error', 'message' => 'Erro ao salvar: ' . $erro];
    }
    mysqli_stmt_close($stmt);

    $campo_id = $id > 0 ? $id : (int) mysqli_insert_id($conn);
    log_acao($conn, 'campos_padrao', $id > 0 ? 'editar' : 'criar', $campo_id, ['slug' => $slug]);
    return ['status' => 'success', 'message' => $msg, 'id' => $campo_id, 'slug' => $slug];
}

function campo_padrao_excluir($conn, $id)
{
    campo_padrao_garantir_estrutura($conn);
    $id = (int) $id;
    $row = campo_padrao_buscar($conn, $id);
    if (!$row) {
        return ['status' => 'error', 'message' => 'Campo não encontrado.'];
    }
    if ((int) $row['sistema'] === 1) {
        return ['status' => 'error', 'message' => 'Campos de sistema não podem ser excluídos.'];
    }

    $stmt = mysqli_prepare($conn, 'UPDATE nps_campos_padrao SET ativo = 0 WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return ['status' => 'error', 'message' => 'Erro ao excluir campo.'];
    }
    mysqli_stmt_close($stmt);
    log_acao($conn, 'campos_padrao', 'excluir', $id, ['slug' => $row['slug'] ?? '']);
    return ['status' => 'success', 'message' => 'Campo padrão excluído com sucesso.'];
}

function campo_padrao_enriquecer_lista($conn, array $lista, $categorias_sugestao = null)
{
    foreach ($lista as &$item) {
        if (!empty($item['campo_padrao_id'])) {
            $padrao = campo_padrao_buscar($conn, (int) $item['campo_padrao_id']);
            $item['campo_padrao_nome'] = $padrao['nome'] ?? '';
            $item['campo_padrao_slug'] = $padrao['slug'] ?? '';
        } elseif (!empty($item['campo_padrao_id'])) {
            // noop
        } else {
            $origem = $item['atributo_nome'] ?? $item['campo_origem'] ?? '';
            $sugerido = campo_padrao_sugerir_por_nome($conn, $origem, $categorias_sugestao);
            $item['campo_padrao_id_sugerido'] = $sugerido;
        }
    }
    unset($item);
    return $lista;
}
