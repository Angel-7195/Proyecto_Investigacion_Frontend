<?php

declare(strict_types=1);

/**
 * Construye las URL de la API sin acceder directamente a MariaDB.
 * En Docker, URL_API debe apuntar al servicio api-investigacion.
 */
function apiUrl(string $recurso = '', string $id = ''): string
{
    $base = getenv('URL_API');

    if ($base === false || trim($base) === '') {
        return '';
    }

    $url = rtrim($base, '/') . '/api/' . rawurlencode($recurso);

    if ($id !== '') {
        $url .= '/' . rawurlencode($id);
    }

    return $url;
}

/**
 * Cliente HTTP genérico: conserva el contrato usado por el panel
 * de consulta de las seis entidades.
 *
 * @return array{codigo:int, datos:array, error:string}
 */
function llamarApi(
    string $metodo,
    string $recurso,
    string $id = '',
    ?array $datos = null
): array {
    $url = apiUrl($recurso, $id);

    if ($url === '') {
        return [
            'codigo' => 0,
            'datos' => [],
            'error' => 'No está configurada la variable URL_API.',
        ];
    }

    $opciones = [
        'http' => [
            'method' => strtoupper($metodo),
            'header' => "Content-Type: application/json; charset=utf-8\r\n"
                . "Accept: application/json\r\n",
            'ignore_errors' => true,
            'timeout' => 10,
        ],
    ];

    if ($datos !== null) {
        // Un PATCH sin campos debe viajar como {}, no como [].
        $json = $datos === []
            ? '{}'
            : json_encode(
                $datos,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

        if ($json === false) {
            return [
                'codigo' => 0,
                'datos' => [],
                'error' => 'No fue posible convertir los datos a JSON.',
            ];
        }

        $opciones['http']['content'] = $json;
    }

    $contexto = stream_context_create($opciones);

    // La variable se establece al ejecutar la petición HTTP.
    $respuesta = @file_get_contents($url, false, $contexto);
    $codigo = 0;

    foreach ($http_response_header ?? [] as $cabecera) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $cabecera, $coincidencia)) {
            $codigo = (int) $coincidencia[1];
        }
    }

    if ($respuesta === false) {
        return [
            'codigo' => $codigo,
            'datos' => [],
            'error' => 'No se pudo conectar con la API.',
        ];
    }

    // 204: petición correcta sin datos ni contenido JSON.
    if ($codigo === 204 || trim($respuesta) === '') {
        return [
            'codigo' => $codigo,
            'datos' => [],
            'error' => '',
        ];
    }

    $decodificado = json_decode($respuesta, true);

    if (!is_array($decodificado)) {
        return [
            'codigo' => $codigo,
            'datos' => [],
            'error' => 'La API devolvió una respuesta JSON no válida.',
        ];
    }

    return [
        'codigo' => $codigo,
        'datos' => $decodificado,
        'error' => '',
    ];
}

/**
 * Adapta llamarApi() al formato que ya utilizan las vistas CRUD:
 * disponible, correcta, estado, datos y error.
 *
 * @return array{disponible:bool, correcta:bool, estado:int, datos:array, error:string}
 */
function adaptarRespuestaApi(array $respuesta): array
{
    $codigo = (int) ($respuesta['codigo'] ?? 0);
    $error = (string) ($respuesta['error'] ?? '');

    return [
        'disponible' => $codigo !== 0,
        'correcta' => $codigo >= 200 && $codigo < 300 && $error === '',
        'estado' => $codigo,
        'datos' => $respuesta['datos'] ?? [],
        'error' => $error,
    ];
}

// ----------------------------------------------------------------------
// TERMINO CLAVE
// ----------------------------------------------------------------------

function listarTerminosClave(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'termino_clave'));
}

function obtenerTerminoClave(string $termino): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'termino_clave', $termino));
}

function crearTerminoClave(array $datos): array
{
    return adaptarRespuestaApi(llamarApi('POST', 'termino_clave', '', $datos));
}

function reemplazarTerminoClave(string $termino, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PUT', 'termino_clave', $termino, $datos));
}

function actualizarTerminoClave(string $termino, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PATCH', 'termino_clave', $termino, $datos));
}

function retirarTerminoClave(string $termino): array
{
    return adaptarRespuestaApi(llamarApi('DELETE', 'termino_clave', $termino));
}

// ----------------------------------------------------------------------
// UNIVERSIDAD
// ----------------------------------------------------------------------

function listarUniversidades(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'universidad'));
}

function obtenerUniversidad(int $id): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'universidad', (string) $id));
}

function crearUniversidad(array $datos): array
{
    return adaptarRespuestaApi(llamarApi('POST', 'universidad', '', $datos));
}

function reemplazarUniversidad(int $id, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PUT', 'universidad', (string) $id, $datos));
}

function actualizarUniversidad(int $id, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PATCH', 'universidad', (string) $id, $datos));
}

function retirarUniversidad(int $id): array
{
    return adaptarRespuestaApi(llamarApi('DELETE', 'universidad', (string) $id));
}

// ----------------------------------------------------------------------
// LINEA DE INVESTIGACION
// ----------------------------------------------------------------------

function listarLineasInvestigacion(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'linea_investigacion'));
}

function obtenerLineaInvestigacion(int $id): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'linea_investigacion', (string) $id));
}

function crearLineaInvestigacion(array $datos): array
{
    // El id se genera en MariaDB; la vista no debe enviarlo.
    return adaptarRespuestaApi(llamarApi('POST', 'linea_investigacion', '', $datos));
}

function reemplazarLineaInvestigacion(int $id, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PUT', 'linea_investigacion', (string) $id, $datos));
}

function actualizarLineaInvestigacion(int $id, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PATCH', 'linea_investigacion', (string) $id, $datos));
}

function retirarLineaInvestigacion(int $id): array
{
    return adaptarRespuestaApi(llamarApi('DELETE', 'linea_investigacion', (string) $id));
}
