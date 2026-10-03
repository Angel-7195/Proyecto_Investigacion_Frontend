<?php

declare(strict_types=1);

function apiUrl(string $recurso = '', string $id = ''): string
{
    $base = rtrim(
        getenv('URL_API') ?: 'http://localhost:8111',
        '/'
    );

    $url = $base . '/api/' . rawurlencode($recurso);

    if ($id !== '') {
        $url .= '/' . rawurlencode($id);
    }

    return $url;
}

function llamarApi(
    string $metodo,
    string $recurso,
    string $id = '',
    ?array $datos = null
): array {
    $url = apiUrl($recurso, $id);

    $opciones = [
        'http' => [
            'method' => strtoupper($metodo),
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'ignore_errors' => true,
            'timeout' => 10,
        ],
    ];

    if ($datos !== null) {
        $opciones['http']['content'] = json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE
        );
    }

    $contexto = stream_context_create($opciones);
    $respuesta = @file_get_contents($url, false, $contexto);

    $codigo = 0;

    foreach ($http_response_header ?? [] as $cabecera) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $cabecera, $coincidencia)) {
            $codigo = (int) $coincidencia[1];
        }
    }

    $resultado = [];

    if ($respuesta !== false && trim($respuesta) !== '') {
        $decodificado = json_decode($respuesta, true);

        if (is_array($decodificado)) {
            $resultado = $decodificado;
        }
    }

    return [
        'codigo' => $codigo,
        'datos' => $resultado,
        'error' => $respuesta === false
            ? 'No se pudo conectar con la API.'
            : '',
    ];
}
