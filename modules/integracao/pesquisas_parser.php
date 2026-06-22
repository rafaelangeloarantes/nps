<?php
/**
 * Parser de pesquisa — mesma fonte da API Guests (participantes)
 */

require_once __DIR__ . '/inteegra_parser.php';

/**
 * Campos disponíveis em um guest: Name, Email e Attributes (Name => Value)
 */
function pesquisa_parser_guest_campos_disponiveis(array $guest)
{
    $campos = [];

    $nome = trim((string) ($guest['Name'] ?? ''));
    if ($nome !== '') {
        $campos['Name'] = $nome;
    }

    $email = trim((string) ($guest['Email'] ?? ''));
    if ($email !== '') {
        $campos['Email'] = $email;
    }

    foreach (inteegra_indexar_atributos($guest) as $attr_nome => $valor) {
        if ($valor !== '') {
            $campos[$attr_nome] = $valor;
        }
    }

    return $campos;
}

function pesquisa_parser_label_campo($origem)
{
    if ($origem === 'Name') {
        return 'Nome';
    }
    if ($origem === 'Email') {
        return 'E-mail';
    }
    return $origem;
}

/**
 * Descobre campos únicos a partir de uma lista de guests (mesma API de participantes)
 */
function pesquisa_parser_descobrir_campos(array $guests)
{
    $campos = [];

    foreach ($guests as $guest) {
        if (!is_array($guest)) {
            continue;
        }

        foreach (pesquisa_parser_guest_campos_disponiveis($guest) as $origem => $valor) {
            if (!isset($campos[$origem])) {
                $campos[$origem] = [
                    'campo_origem' => $origem,
                    'campo_label' => pesquisa_parser_label_campo($origem),
                    'exemplo_valor' => mb_substr($valor, 0, 120, 'UTF-8'),
                ];
            }
        }
    }

    $lista = array_values($campos);
    usort($lista, function ($a, $b) {
        if ($a['campo_origem'] === 'Name') {
            return -1;
        }
        if ($b['campo_origem'] === 'Name') {
            return 1;
        }
        if ($a['campo_origem'] === 'Email') {
            return -1;
        }
        if ($b['campo_origem'] === 'Email') {
            return 1;
        }
        return strcasecmp($a['campo_origem'], $b['campo_origem']);
    });

    return $lista;
}

/**
 * Aplica mapeamento salvo sobre um guest da API
 */
function pesquisa_parser_aplicar_mapeamento(array $guest, array $mapeamentos)
{
    $flat = pesquisa_parser_guest_campos_disponiveis($guest);
    $dados = [];
    $email = trim(strtolower((string) ($guest['Email'] ?? '')));

    foreach ($mapeamentos as $map) {
        if ((int) ($map['importar'] ?? 0) !== 1) {
            continue;
        }

        $origem = $map['campo_origem'] ?? '';
        if ($origem === '' || !array_key_exists($origem, $flat)) {
            continue;
        }

        $valor = $flat[$origem];
        $label = trim($map['campo_label'] ?? pesquisa_parser_label_campo($origem));
        $dados[$label] = $valor;

        if ($email === '' && ($origem === 'Email' || stripos($origem, 'email') !== false || stripos($origem, 'e-mail') !== false)) {
            $email = trim(strtolower($valor));
        }
    }

    if ($email === '' && !empty($flat['Email'])) {
        $email = trim(strtolower($flat['Email']));
    }

    return [
        'email' => $email,
        'dados' => $dados,
        'bruto' => $flat,
        'guest_id' => (int) ($guest['Id'] ?? 0),
    ];
}
