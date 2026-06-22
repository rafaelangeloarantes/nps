<?php
/**
 * Parser do JSON Guests — Name, Email e Attributes (Name/Value)
 */

function inteegra_normalizar_nome_atributo($nome, $max = 255)
{
    $nome = preg_replace('/\s+/u', ' ', trim((string) $nome));
    return mb_substr($nome, 0, $max, 'UTF-8');
}

/**
 * Índice de atributos customizados do evento: Nome => Value
 */
function inteegra_indexar_atributos(array $guest)
{
    $mapa = [];
    foreach ($guest['Attributes'] ?? [] as $item) {
        if (!is_array($item)) {
            continue;
        }
        $nome = inteegra_normalizar_nome_atributo($item['Attribute']['Name'] ?? '');
        if ($nome === '') {
            continue;
        }
        $mapa[$nome] = trim((string) ($item['Value'] ?? ''));
    }
    return $mapa;
}

/**
 * Descobre atributos únicos a partir de Attributes[] (escopo do evento no Inteegra)
 */
function inteegra_descobrir_atributos(array $guests)
{
    $atributos = [];

    foreach ($guests as $guest) {
        if (!is_array($guest)) {
            continue;
        }
        foreach ($guest['Attributes'] ?? [] as $item) {
            if (!is_array($item) || !isset($item['Attribute'])) {
                continue;
            }
            $nome = inteegra_normalizar_nome_atributo($item['Attribute']['Name'] ?? '');
            if ($nome === '') {
                continue;
            }
            $attr_id = (int) ($item['AttributeId'] ?? $item['Attribute']['Id'] ?? 0);
            if (!isset($atributos[$nome])) {
                $atributos[$nome] = [
                    'atributo_nome' => $nome,
                    'atributo_id_api' => $attr_id ?: null,
                    'exemplo_valor' => trim((string) ($item['Value'] ?? '')),
                ];
            }
        }
    }

    usort($atributos, function ($a, $b) {
        return strcasecmp($a['atributo_nome'], $b['atributo_nome']);
    });

    return array_values($atributos);
}

/**
 * Sugestão automática de campo destino pelo nome do atributo
 */
function inteegra_sugerir_campo_destino($atributo_nome)
{
    $nome = mb_strtolower(trim($atributo_nome), 'UTF-8');
    $mapa = [
        'nome' => 'nome_completo',
        'name' => 'nome_completo',
        'email' => 'email',
        'e-mail' => 'email',
        'cargo' => 'cargo',
        'empresa' => 'empresa',
        'estado' => 'estado',
        'cidade' => 'cidade',
        'celular' => 'telefone',
        'telefone' => 'telefone',
        'linkedin' => 'linkedin',
        'data de nascimento' => 'data_nascimento',
        'nascimento' => 'data_nascimento',
        'cpf' => 'extra',
        'endereço' => 'extra',
        'endereco' => 'extra',
        'cep' => 'extra',
        'grupo' => 'extra',
        'nome crachá' => 'extra',
        'nome cracha' => 'extra',
    ];

    if (isset($mapa[$nome])) {
        return $mapa[$nome];
    }

    if (strpos($nome, 'utm_') === 0) {
        return 'extra';
    }

    return 'extra';
}

/**
 * Credenciamento SHOW/NOSHOW conforme painel Inteegra (Gerencial).
 * Show = guest com AttendDate (data/hora do credenciamento).
 * Não credenciado = confirmado (CN) sem AttendDate.
 * Pendentes (NE) e demais: sem registro de credenciamento (null).
 */
function inteegra_credenciamento_status_de_guest(array $guest)
{
    $attend = trim((string) ($guest['AttendDate'] ?? ''));
    if ($attend !== '') {
        return 'SHOW';
    }

    $conf = strtoupper(trim((string) ($guest['ConfirmationStatus'] ?? '')));
    if (strpos($conf, 'CN') === 0) {
        return 'NOSHOW';
    }

    return null;
}

/**
 * Guest confirmado no painel Inteegra (ConfirmationStatus CN*)
 */
function inteegra_guest_confirmado_cn($confirmation_status)
{
    $conf = strtoupper(trim((string) $confirmation_status));
    return $conf !== '' && strpos($conf, 'CN') === 0;
}

/**
 * Converte guest da API em dados do participante conforme mapeamento do evento
 */
function inteegra_parse_guest_participante(array $guest, array $mapeamentos)
{
    $nome_raiz = trim($guest['Name'] ?? '');
    $email_raiz = trim(strtolower($guest['Email'] ?? ''));
    $guest_id = (int) ($guest['Id'] ?? 0);

    $attrs = inteegra_indexar_atributos($guest);

    $participante = [
        'nome_completo' => $nome_raiz,
        'email' => $email_raiz,
        'telefone' => '',
        'cargo' => '',
        'empresa' => '',
        'estado' => '',
        'cidade' => '',
        'data_nascimento' => null,
        'linkedin' => '',
    ];

    $extras = [];

    foreach ($mapeamentos as $map) {
        if (empty($map['importar'])) {
            continue;
        }

        $attr_nome = $map['atributo_nome'] ?? '';
        $valor = $attrs[$attr_nome] ?? '';
        $destino = $map['campo_destino'] ?? 'extra';

        if ($valor === '') {
            continue;
        }

        if ($destino === 'extra') {
            $extras[$attr_nome] = $valor;
        } elseif (array_key_exists($destino, $participante)) {
            if ($destino === 'data_nascimento') {
                $participante[$destino] = inteegra_normalizar_data($valor);
            } else {
                $participante[$destino] = $valor;
            }
        }
    }

    // Raiz sempre prevalece para nome e e-mail
    if ($nome_raiz !== '') {
        $participante['nome_completo'] = $nome_raiz;
    }
    if ($email_raiz !== '') {
        $participante['email'] = $email_raiz;
    }

    // Fallback: atributos Nome/Email se raiz vazia
    if ($participante['nome_completo'] === '' && !empty($attrs['Nome'])) {
        $participante['nome_completo'] = $attrs['Nome'];
    }
    if ($participante['email'] === '' && !empty($attrs['Email'])) {
        $participante['email'] = trim(strtolower($attrs['Email']));
    }

    return [
        'participante' => $participante,
        'extras' => $extras,
        'guest_id' => $guest_id,
        'email_original_api' => $email_raiz,
        'atributos_brutos' => $attrs,
        'confirmation_status' => strtoupper(trim((string) ($guest['ConfirmationStatus'] ?? ''))),
        'credenciamento_status' => inteegra_credenciamento_status_de_guest($guest),
    ];
}

function inteegra_normalizar_data($valor)
{
    $valor = trim($valor);
    if ($valor === '') {
        return null;
    }
    $ts = strtotime($valor);
    return $ts ? date('Y-m-d', $ts) : null;
}
