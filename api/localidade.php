<?php
/**
 * API Localidade — System Designer (template base)
 *
 * Consome data/all.json (países, estados e cidades do mundo).
 * Cache em arquivo: decode do JSON só quando all.json muda. Dados estáticos = ideal para cache.
 *
 * Uso:
 *   (sem parâmetros) ou ?list=paises → lista de países (sigla, nome)
 *   ?pais=BR → estados do país (state_code, nome)
 *   ?pais=BR&estado=AC → cidades do estado (array de strings)
 */
header('Content-Type: application/json; charset=utf-8');

$base = dirname(__DIR__);
$jsonPath = $base . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'all.json';
$cacheDir = $base . DIRECTORY_SEPARATOR . 'cache';
$cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'localidade_data.cache';

if (!is_readable($jsonPath)) {
    http_response_code(500);
    echo json_encode(['erro' => 'Arquivo data/all.json não encontrado.']);
    exit;
}

$jsonMtime = filemtime($jsonPath);
$useCache = is_file($cacheFile) && filemtime($cacheFile) >= $jsonMtime;

if ($useCache) {
    $data = @unserialize(file_get_contents($cacheFile));
}

if (!isset($data) || !is_array($data)) {
    $json = file_get_contents($jsonPath);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        http_response_code(500);
        echo json_encode(['erro' => 'Formato inválido em all.json.']);
        exit;
    }
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    if (is_writable($cacheDir)) {
        @file_put_contents($cacheFile, serialize($data), LOCK_EX);
    }
}

$pais = isset($_GET['pais']) ? strtoupper(trim((string) $_GET['pais'])) : null;
$estado = isset($_GET['estado']) ? trim((string) $_GET['estado']) : null;

// Cidades: ?pais=BR&estado=AC
if ($pais !== null && $pais !== '' && $estado !== null && $estado !== '') {
    foreach ($data as $c) {
        if (isset($c['iso2']) && $c['iso2'] === $pais && !empty($c['states'])) {
            foreach ($c['states'] as $s) {
                $code = isset($s['state_code']) ? trim($s['state_code']) : '';
                if ($code === $estado && isset($s['cities']) && is_array($s['cities'])) {
                    echo json_encode($s['cities']);
                    exit;
                }
            }
            echo json_encode([]);
            exit;
        }
    }
    echo json_encode([]);
    exit;
}

// Estados: ?pais=BR
if ($pais !== null && $pais !== '') {
    foreach ($data as $c) {
        if (isset($c['iso2']) && $c['iso2'] === $pais && isset($c['states']) && is_array($c['states'])) {
            $lista = [];
            foreach ($c['states'] as $s) {
                if (isset($s['name'], $s['state_code'])) {
                    $lista[] = ['sigla' => $s['state_code'], 'nome' => $s['name']];
                }
            }
            echo json_encode($lista);
            exit;
        }
    }
    echo json_encode([]);
    exit;
}

// Países (sem parâmetro ou ?list=paises)
$lista = [];
foreach ($data as $c) {
    if (isset($c['iso2'])) {
        $nome = isset($c['translations']['br']) && $c['translations']['br'] !== ''
            ? $c['translations']['br']
            : (isset($c['name']) ? $c['name'] : '');
        if ($nome !== '') {
            $lista[] = ['sigla' => $c['iso2'], 'nome' => $nome];
        }
    }
}
usort($lista, function ($a, $b) {
    return strcoll($a['nome'], $b['nome']);
});
echo json_encode($lista);
