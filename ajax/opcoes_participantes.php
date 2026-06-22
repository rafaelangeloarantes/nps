<?php

require_once __DIR__ . '/../bootstrap.php';

require_once __DIR__ . '/../modules/auth/middleware.php';



$evento_id = (int) ($_GET['evento_id'] ?? 0);



if ($evento_id > 0) {

    $stmt = mysqli_prepare(

        $conn,

        'SELECT p.id, p.nome_completo, p.email

         FROM participantes p

         INNER JOIN participante_eventos pe ON pe.participante_id = p.id AND pe.evento_id = ?

         WHERE p.ativo = 1

         ORDER BY p.nome_completo ASC'

    );

    mysqli_stmt_bind_param($stmt, 'i', $evento_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

} else {

    $result = mysqli_query($conn, 'SELECT id, nome_completo, email FROM participantes WHERE ativo = 1 ORDER BY nome_completo ASC');

}



$lista = [];

while ($row = mysqli_fetch_assoc($result)) {

    $lista[] = $row;

}

if (isset($stmt)) {

    mysqli_stmt_close($stmt);

}



header('Content-Type: application/json; charset=utf-8');

echo json_encode(['data' => $lista]);

