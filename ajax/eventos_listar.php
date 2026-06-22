<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';

$draw = (int) ($_GET['draw'] ?? 1);
[$start, $length] = datatable_paginacao($_GET['start'] ?? 0, $_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');
$contrato_id = (int) ($_GET['contrato_id'] ?? 0);

$where = 'e.ativo = 1';
if ($contrato_id > 0) {
    exigir_acesso_contrato($conn, $contrato_id);
    $where .= ' AND e.contrato_id = ' . $contrato_id;
}
$where .= sql_filtro_contrato('e.contrato_id');


$total = datatable_count($conn, "SELECT COUNT(*) AS t FROM eventos e WHERE {$where}");

$sql = "SELECT e.id, e.nome, e.data_inicio,
               e.id_integracao, e.ultima_sincronizacao,
               (SELECT COUNT(DISTINCT pe.participante_id)
                FROM participante_eventos pe
                INNER JOIN participantes p ON p.id = pe.participante_id AND p.ativo = 1
                WHERE pe.evento_id = e.id) AS total_convocados,
               (SELECT COUNT(*)
                FROM credenciamentos cr
                INNER JOIN participantes p ON p.id = cr.participante_id AND p.ativo = 1
                WHERE cr.evento_id = e.id AND cr.ativo = 1 AND cr.status = 'SHOW') AS total_show,
               (SELECT COUNT(DISTINCT pe.participante_id)
                FROM participante_eventos pe
                INNER JOIN participantes p ON p.id = pe.participante_id AND p.ativo = 1
                WHERE pe.evento_id = e.id
                  AND pe.confirmation_status_api LIKE 'CN%') AS total_confirmados
        FROM eventos e
        INNER JOIN contratos c ON c.id = e.contrato_id
        WHERE {$where}";

if ($search !== '') {
    $s = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $sql .= " AND (e.nome LIKE '{$s}' OR c.nome LIKE '{$s}' OR e.id_integracao LIKE '{$s}')";
}

$count_sql = "SELECT COUNT(*) AS t
        FROM eventos e
        INNER JOIN contratos c ON c.id = e.contrato_id
        WHERE {$where}";
if ($search !== '') {
    $count_sql .= " AND (e.nome LIKE '{$s}' OR c.nome LIKE '{$s}' OR e.id_integracao LIKE '{$s}')";
}
$total_filtrado = datatable_count($conn, $count_sql);
$sql .= " ORDER BY e.data_inicio DESC, e.id DESC LIMIT {$start}, {$length}";
$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $convidados = (int) $row['total_convocados'];
    $show = (int) $row['total_show'];
    $confirmados = (int) $row['total_confirmados'];
    // Sem status CN sincronizado ainda: mantém fallback pelo total de convidados
    if ($confirmados <= 0 && $convidados > 0) {
        $confirmados = $convidados;
    }
    $noshow = max(0, $confirmados - $show);

    $data[] = [
        'id_integracao' => $row['id_integracao'] ?: '—',
        'nome' => $row['nome'],
        'convidados' => $convidados,
        'confirmados' => $confirmados,
        'show' => $show,
        'noshow' => $noshow,
        'ultima_sync' => formatar_data_hora($row['ultima_sincronizacao']),
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
