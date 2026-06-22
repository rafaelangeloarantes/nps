<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/credenciamentos/functions.php';

$draw = (int) ($_GET['draw'] ?? 1);
[$start, $length] = datatable_paginacao($_GET['start'] ?? 0, $_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');
$evento_id = (int) ($_GET['evento_id'] ?? 0);
$com_pesquisa = (int) ($_GET['com_pesquisa'] ?? 0);

$join = '';
$where = 'p.ativo = 1';
if ($evento_id > 0) {
    exigir_acesso_evento($conn, $evento_id);
    $join = ' INNER JOIN participante_eventos pe ON pe.participante_id = p.id AND pe.evento_id = ' . $evento_id;
} elseif (!eh_admin_master()) {
    $cid = obter_contrato_usuario();
    if ($cid) {
        $where .= " AND EXISTS (
            SELECT 1 FROM participante_eventos pe_c
            INNER JOIN eventos ev ON ev.id = pe_c.evento_id AND ev.ativo = 1
            WHERE pe_c.participante_id = p.id AND ev.contrato_id = {$cid}
        )";
    } else {
        $where .= ' AND 1=0';
    }
}

$pesquisa_exists = 'EXISTS (
    SELECT 1
    FROM relatorio_pesquisa_respostas r
    INNER JOIN pesquisas ps ON ps.id = r.pesquisa_id AND ps.ativo = 1
    WHERE r.participante_id = p.id';
if ($evento_id > 0) {
    $pesquisa_exists .= ' AND COALESCE(r.evento_id, ps.evento_id) = ' . $evento_id;
}
$pesquisa_exists .= ')';

if ($com_pesquisa === 1) {
    $where .= " AND {$pesquisa_exists}";
}

$total = datatable_count($conn, "SELECT COUNT(DISTINCT p.id) AS t FROM participantes p {$join} WHERE {$where}");

$count_sql = "SELECT COUNT(DISTINCT p.id) AS t FROM participantes p {$join} WHERE {$where}";
if ($search !== '') {
    $s = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $count_sql .= " AND (p.nome_completo LIKE '{$s}' OR p.email LIKE '{$s}' OR p.empresa LIKE '{$s}')";
}
$total_filtrado = datatable_count($conn, $count_sql);

$confirmation_col = $evento_id > 0 ? 'pe.confirmation_status_api' : 'NULL AS confirmation_status_api';

$sql = "SELECT DISTINCT p.id, p.nome_completo, p.email, p.empresa,
               p.dado_incompleto, p.motivo_incompleto,
               cr.status AS credenciamento_status,
               {$confirmation_col},
               (SELECT COUNT(*)
                FROM relatorio_pesquisa_respostas r
                INNER JOIN pesquisas ps ON ps.id = r.pesquisa_id AND ps.ativo = 1
                WHERE r.participante_id = p.id";
if ($evento_id > 0) {
    $sql .= ' AND COALESCE(r.evento_id, ps.evento_id) = ' . $evento_id;
}
$sql .= ") AS total_pesquisas
        FROM participantes p {$join}";
if ($evento_id > 0) {
    $sql .= ' LEFT JOIN credenciamentos cr ON cr.participante_id = p.id AND cr.evento_id = ' . $evento_id . ' AND cr.ativo = 1';
} else {
    $sql .= ' LEFT JOIN credenciamentos cr ON 1=0';
}
$sql .= " WHERE {$where}";

if ($search !== '') {
    $s = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $sql .= " AND (p.nome_completo LIKE '{$s}' OR p.email LIKE '{$s}' OR p.empresa LIKE '{$s}')";
}
$sql .= " ORDER BY p.id DESC LIMIT {$start}, {$length}";
$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cred = '—';
    if ($evento_id > 0) {
        $status_exib = credenciamento_resolver_status_exibicao(
            $row['credenciamento_status'] ?? null,
            $row['confirmation_status_api'] ?? null
        );
        $cred = credenciamento_status_badge_html($status_exib);
    }

    $data[] = [
        'id' => $row['id'],
        'nome_completo' => $row['nome_completo'],
        'email' => $row['email'] ?: '—',
        'empresa' => $row['empresa'] ?: '—',
        'integridade' => badge_incompleto($row['dado_incompleto'], $row['motivo_incompleto']),
        'credenciamento' => $cred,
        'total_pesquisas' => (int) ($row['total_pesquisas'] ?? 0),
        'acoes' => (int) $row['id'],
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $total_filtrado,
    'data' => $data,
]);
