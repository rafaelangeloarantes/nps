<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require __DIR__ . '/../bootstrap.php';

$tables = ['participantes', 'participante_eventos', 'credenciamentos', 'relatorio_pesquisa_campos'];
foreach ($tables as $t) {
    $r = mysqli_query($conn, "SHOW TABLES LIKE '{$t}'");
    echo $t . ': ' . (mysqli_num_rows($r) ? 'EXISTS' : 'MISSING') . PHP_EOL;
}
