<?php
/**
 * Cliente HTTP — API externa Inteegra (Guests / Token)
 */

define('INTEGRA_API_AUTH_BASE_DEFAULT', 'https://api-externa.inteegra.com.br/security/security');
define('INTEGRA_API_GUESTS_BASE_DEFAULT', 'https://api-externa.inteegra.com.br/public');
define('INTEGRA_API_DOC_URL', 'https://documenter.getpostman.com/view/25025078/2s93XtzjTG#intro');

function inteegra_auth_base($override = null)
{
    if ($override !== null && trim($override) !== '') {
        return rtrim(trim($override), '/');
    }
    return rtrim($_ENV['INTEGRA_API_AUTH_BASE'] ?? INTEGRA_API_AUTH_BASE_DEFAULT, '/');
}

function inteegra_guests_base($override = null)
{
    if ($override !== null && trim($override) !== '') {
        return rtrim(trim($override), '/');
    }
    // Compatibilidade: INTEGRA_API_BASE antigo = base de guests
    $env = $_ENV['INTEGRA_API_GUESTS_BASE'] ?? $_ENV['INTEGRA_API_BASE'] ?? INTEGRA_API_GUESTS_BASE_DEFAULT;
    return rtrim($env, '/');
}

/**
 * Resolve bases da API — token (security) e guests (public) são endpoints distintos
 */
function inteegra_resolver_bases_api($evento, $contrato = null, $integracao = null)
{
    $guests_override = '';
    $evento_link = trim($evento['link'] ?? '');
    if ($evento_link !== '' && preg_match('#^https?://#i', $evento_link)) {
        $guests_override = rtrim($evento_link, '/');
    }

    $auth_override = null;
    $guests_config = null;
    if (is_array($integracao)) {
        $auth_override = trim($integracao['url_auth_base'] ?? '') ?: null;
        $guests_config = trim($integracao['url_api_base'] ?? '') ?: null;
    }

    return [
        'auth_base' => inteegra_auth_base($auth_override),
        'guests_base' => inteegra_guests_base($guests_override ?: $guests_config),
    ];
}

/**
 * Resolve login/senha: evento > contrato > integração global
 */
function inteegra_resolver_credenciais_acesso($evento, $contrato, array $cred_global)
{
    $candidatos = [
        ['origem' => 'evento', 'login' => trim($evento['usuario_acesso'] ?? ''), 'senha' => $evento['senha_acesso'] ?? ''],
        ['origem' => 'contrato', 'login' => trim($contrato['usuario_acesso'] ?? ''), 'senha' => $contrato['senha_acesso'] ?? ''],
    ];

    foreach ($candidatos as $item) {
        if ($item['login'] === '') {
            continue;
        }
        $senha = descriptografar_texto($item['senha']);
        if ($senha === '' && $item['senha'] !== '') {
            continue;
        }
        if ($senha !== '') {
            return [
                'ok' => true,
                'login' => $item['login'],
                'password' => $senha,
                'origem' => $item['origem'],
            ];
        }
    }

    return [
        'ok' => true,
        'login' => $cred_global['login'],
        'password' => $cred_global['password'],
        'origem' => 'integracao',
    ];
}

/**
 * Metadados do evento na API externa
 */
function inteegra_buscar_evento_api($token, $event_id_api, $guests_base = null)
{
    $url = inteegra_guests_base($guests_base) . '/api/events/' . rawurlencode((string) $event_id_api);
    $result = inteegra_http_request('GET', $url, null, $token);
    if (!$result['ok'] || !is_array($result['data'])) {
        return null;
    }
    return $result['data'];
}

/**
 * Mensagem quando /api/guests retorna vazio
 */
function inteegra_mensagem_sem_guests($event_id_api, $login, $evento_api = null)
{
    $nome = is_array($evento_api) ? trim($evento_api['Name'] ?? '') : '';
    $msg = 'A API não retornou participantes para o EventId ' . $event_id_api;
    if ($nome !== '') {
        $msg .= ' (' . $nome . ')';
    }
    $msg .= '.';

    if (is_array($evento_api)) {
        return $msg . ' O evento existe na API, mas o usuário de integração "'
            . $login . '" não tem permissão para listar convidados deste evento. '
            . 'Solicite à Inteegra a liberação do acesso ou configure credenciais específicas no contrato/evento.';
    }

    return $msg . ' Verifique o ID de integração ou as permissões do usuário "' . $login . '" na API externa.';
}

/**
 * Decodifica JSON da resposta HTTP com tolerância a BOM e lixo
 */
function inteegra_decodificar_json($body)
{
    if ($body === null || $body === '') {
        return [null, 'Corpo da resposta vazio.'];
    }

    if (!is_string($body)) {
        return [null, 'Corpo da resposta inválido.'];
    }

    // Remove BOM UTF-8
    $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);
    $body = trim($body);

    if ($body === '') {
        return [null, 'Corpo da resposta vazio.'];
    }

    $data = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return [$data, null];
    }

    // Tenta extrair primeiro bloco JSON (array ou objeto)
    if (preg_match('/(\[[\s\S]*\]|\{[\s\S]*\})/', $body, $m)) {
        $data = json_decode($m[1], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return [$data, null];
        }
    }

    return [null, 'Resposta não é JSON válido: ' . json_last_error_msg()];
}

/**
 * Mensagem de erro amigável para falhas HTTP/API
 */
function inteegra_erro_http($http_code, $parse_error, $raw = '')
{
    if ($http_code === 401 || $http_code === 403) {
        return 'Acesso negado pela API (HTTP ' . $http_code . '). Verifique usuário e senha em Configurações > Integrações.';
    }
    if ($http_code === 404) {
        return 'Endpoint da API não encontrado (HTTP 404). Token: /security/security/api/users/token — Guests: /public/api/guests';
    }
    if ($http_code >= 500) {
        return 'API indisponível (HTTP ' . $http_code . '). Tente novamente mais tarde.';
    }
    if ($parse_error) {
        $preview = preg_replace('/\s+/', ' ', substr(strip_tags((string) $raw), 0, 120));
        if ($preview !== '') {
            return $parse_error . ' HTTP ' . $http_code . '. Início: ' . $preview;
        }
        return $parse_error . ' (HTTP ' . $http_code . ').';
    }
    return 'Falha na API (HTTP ' . $http_code . ').';
}

/**
 * Normaliza lista de guests (array direto ou envelope)
 */
function inteegra_normalizar_lista_guests($data)
{
    if (!is_array($data)) {
        return null;
    }

    if (isset($data[0]) || $data === []) {
        return $data;
    }

    foreach (['data', 'Data', 'items', 'Items', 'guests', 'Guests', 'results', 'Results'] as $key) {
        if (isset($data[$key]) && is_array($data[$key])) {
            return $data[$key];
        }
    }

    return null;
}

/**
 * Requisição HTTP via cURL
 */
function inteegra_http_request($method, $url, $body = null, $bearer_token = null, &$response_headers = [])
{
    $response_headers = [];
    $ch = curl_init($url);
    $headers = ['Accept: application/json', 'User-Agent: NPS-Relatorios/1.0'];

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json; charset=utf-8';
    }
    if ($bearer_token) {
        $headers[] = 'Authorization: Bearer ' . $bearer_token;
    }

    $method = strtoupper($method);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_ENCODING => '',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADERFUNCTION => function ($curl, $header_line) use (&$response_headers) {
            $parts = explode(':', $header_line, 2);
            if (count($parts) === 2) {
                $response_headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($header_line);
        },
    ];

    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);
        }
    } else {
        $opts[CURLOPT_CUSTOMREQUEST] = $method;
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);
        }
    }

    curl_setopt_array($ch, $opts);

    $response = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'Erro de conexão: ' . $curl_error,
            'data' => null,
            'headers' => $response_headers,
        ];
    }

    list($data, $parse_error) = inteegra_decodificar_json($response);

    if ($parse_error !== null) {
        error_log('Inteegra API JSON falhou: ' . $url . ' HTTP ' . $http_code . ' — ' . substr($response, 0, 300));
        return [
            'ok' => false,
            'http_code' => $http_code,
            'error' => inteegra_erro_http($http_code, $parse_error, $response),
            'data' => null,
            'headers' => $response_headers,
            'raw' => $response,
        ];
    }

    return [
        'ok' => $http_code >= 200 && $http_code < 300,
        'http_code' => $http_code,
        'error' => $http_code >= 200 && $http_code < 300 ? null : inteegra_erro_http($http_code, null, $response),
        'data' => $data,
        'headers' => $response_headers,
    ];
}

/**
 * Obtém Bearer Token
 */
function inteegra_obter_token($login, $password, $auth_base = null)
{
    $login = trim($login);
    $password = (string) $password;

    if ($login === '' || $password === '') {
        return ['ok' => false, 'error' => 'Credenciais de acesso não configuradas em Configurações > Integrações.'];
    }

    $url = inteegra_auth_base($auth_base) . '/api/users/token';
    $result = inteegra_http_request('POST', $url, [
        'Login' => $login,
        'Password' => $password,
    ]);

    if (!$result['ok']) {
        $msg = $result['error'] ?? 'Falha ao autenticar na API.';
        if (is_array($result['data']) && !empty($result['data']['message'])) {
            $msg = $result['data']['message'];
        }
        return ['ok' => false, 'error' => $msg];
    }

    $token = $result['data']['token'] ?? $result['data']['Token'] ?? '';
    if ($token === '') {
        return ['ok' => false, 'error' => 'Token não retornado pela API.'];
    }

    return ['ok' => true, 'token' => $token];
}

/**
 * Parse do header Pagination da API
 */
function inteegra_parse_pagination_header($headers)
{
    $raw = $headers['pagination'] ?? '';
    if ($raw === '') {
        return null;
    }
    $parsed = json_decode($raw, true);
    return is_array($parsed) ? $parsed : null;
}

/**
 * Busca uma página de guests
 */
function inteegra_buscar_guests_pagina($token, $event_id_api, $page_number = 1, $page_size = 50, $guests_base = null)
{
    $query = http_build_query([
        'EventId' => $event_id_api,
        'PageNumber' => (int) $page_number,
        'PageSize' => (int) $page_size,
    ]);
    $url = inteegra_guests_base($guests_base) . '/api/guests?' . $query;
    $headers = [];
    $result = inteegra_http_request('GET', $url, null, $token, $headers);

    if (!$result['ok']) {
        $msg = $result['error'] ?? 'Falha ao buscar participantes na API.';
        if (is_array($result['data']) && !empty($result['data']['message'])) {
            $msg = $result['data']['message'];
        }
        return ['ok' => false, 'error' => $msg, 'guests' => [], 'pagination' => null];
    }

    $guests = inteegra_normalizar_lista_guests($result['data']);
    if ($guests === null) {
        return ['ok' => false, 'error' => 'Formato inesperado na resposta de guests.', 'guests' => [], 'pagination' => null];
    }

    return [
        'ok' => true,
        'guests' => $guests,
        'pagination' => inteegra_parse_pagination_header($headers),
    ];
}

/**
 * Busca todos os guests com paginação automática
 */
function inteegra_buscar_todos_guests($token, $event_id_api, $page_size = 50, $guests_base = null)
{
    $todos = [];
    $page = 1;
    $total_pages = 1;

    do {
        $res = inteegra_buscar_guests_pagina($token, $event_id_api, $page, $page_size, $guests_base);
        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error'], 'guests' => $todos];
        }

        $todos = array_merge($todos, $res['guests']);
        $pagination = $res['pagination'];

        if ($pagination && isset($pagination['TotalPages'])) {
            $total_pages = (int) $pagination['TotalPages'];
        } elseif (count($res['guests']) < $page_size) {
            break;
        }

        $page++;
    } while ($page <= $total_pages);

    return ['ok' => true, 'guests' => $todos, 'total' => count($todos)];
}

/**
 * Credenciais do evento (fallback para contrato)
 */
function inteegra_credenciais_evento($conn, $evento_id)
{
    require_once __DIR__ . '/../eventos/functions.php';
    require_once __DIR__ . '/../contratos/functions.php';
    require_once __DIR__ . '/../integracoes/functions.php';

    $evento = evento_buscar_por_id($conn, $evento_id);
    if (!$evento) {
        return ['ok' => false, 'error' => 'Evento não encontrado.'];
    }

    $cred = integracao_credenciais($conn, 'inteegra');
    if (!$cred['ok']) {
        return $cred;
    }

    $contrato = contrato_buscar_por_id($conn, (int) $evento['contrato_id']);
    $bases = inteegra_resolver_bases_api($evento, $contrato, $cred['integracao']);
    $acesso = inteegra_resolver_credenciais_acesso($evento, $contrato ?: [], $cred);

    $event_id_api = trim($evento['id_integracao'] ?? '');
    if ($event_id_api === '') {
        return ['ok' => false, 'error' => 'ID de integração não configurado no evento.'];
    }

    return [
        'ok' => true,
        'login' => $acesso['login'],
        'password' => $acesso['password'],
        'credencial_origem' => $acesso['origem'],
        'event_id_api' => $event_id_api,
        'auth_base' => $bases['auth_base'],
        'guests_base' => $bases['guests_base'],
        'evento' => $evento,
        'contrato' => $contrato,
    ];
}

/**
 * Autentica e retorna token para o evento
 */
function inteegra_autenticar_evento($conn, $evento_id)
{
    $cred = inteegra_credenciais_evento($conn, $evento_id);
    if (!$cred['ok']) {
        return $cred;
    }

    $token_res = inteegra_obter_token($cred['login'], $cred['password'], $cred['auth_base'] ?? null);
    if (!$token_res['ok']) {
        return $token_res;
    }

    return [
        'ok' => true,
        'token' => $token_res['token'],
        'login' => $cred['login'],
        'credencial_origem' => $cred['credencial_origem'] ?? 'integracao',
        'event_id_api' => $cred['event_id_api'],
        'auth_base' => $cred['auth_base'],
        'guests_base' => $cred['guests_base'],
        'evento' => $cred['evento'],
    ];
}
