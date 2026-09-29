<?php

declare(strict_types=1);

/**
 * Envía una petición HTTP a la API de Investigación.
 *
 * Este archivo es el único punto del frontend que utiliza cURL
 * para comunicarse con la API.
 *
 * @return array{
 *     disponible: bool,
 *     correcta: bool,
 *     estado: int,
 *     datos: mixed,
 *     error: ?string
 * }
 */
function enviarPeticionApi(
    string $metodo,
    string $ruta,
    ?array $cuerpo = null
): array {
    $urlApi = getenv('URL_API');

    if ($urlApi === false || trim($urlApi) === '') {
        return [
            'disponible' => false,
            'correcta' => false,
            'estado' => 0,
            'datos' => null,
            'error' => 'No se encuentra configurada la URL del servicio.',
        ];
    }

    $url = rtrim($urlApi, '/')
        . '/'
        . ltrim($ruta, '/');

    $curl = curl_init($url);

    if ($curl === false) {
        return [
            'disponible' => false,
            'correcta' => false,
            'estado' => 0,
            'datos' => null,
            'error' => 'No fue posible iniciar la comunicación con el servicio.',
        ];
    }

    $encabezados = [
        'Accept: application/json',
    ];

    $opciones = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($metodo),
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 8,
    ];

    if ($cuerpo !== null) {
        /*
         * Para una petición sin campos enviamos {} y no [].
         */
        $json = $cuerpo === []
            ? '{}'
            : json_encode(
                $cuerpo,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

        if ($json === false) {
            curl_close($curl);

            return [
                'disponible' => true,
                'correcta' => false,
                'estado' => 0,
                'datos' => null,
                'error' => 'No fue posible preparar los datos de la petición.',
            ];
        }

        $opciones[CURLOPT_POSTFIELDS] = $json;

        $encabezados[] = 'Content-Type: application/json';
    }

    $opciones[CURLOPT_HTTPHEADER] = $encabezados;

    curl_setopt_array($curl, $opciones);

    $respuesta = curl_exec($curl);

    if ($respuesta === false) {
        curl_close($curl);

        return [
            'disponible' => false,
            'correcta' => false,
            'estado' => 0,
            'datos' => null,
            'error' => 'El servicio no está disponible en este momento.',
        ];
    }

    $estado = (int) curl_getinfo(
        $curl,
        CURLINFO_RESPONSE_CODE
    );

    curl_close($curl);

    /*
     * Un 204 significa que la petición fue correcta,
     * pero no existe contenido que decodificar.
     */
    if ($estado === 204 || trim($respuesta) === '') {
        return [
            'disponible' => true,
            'correcta' => $estado >= 200 && $estado < 300,
            'estado' => $estado,
            'datos' => null,
            'error' => null,
        ];
    }

    $datos = json_decode(
        $respuesta,
        true
    );

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'disponible' => true,
            'correcta' => false,
            'estado' => $estado,
            'datos' => null,
            'error' => 'El servicio devolvió una respuesta no válida.',
        ];
    }

    return [
        'disponible' => true,
        'correcta' => $estado >= 200 && $estado < 300,
        'estado' => $estado,
        'datos' => $datos,
        'error' => null,
    ];
}


// ----------------------------------------------------------------------
// TERMINO CLAVE
// ----------------------------------------------------------------------

function listarTerminosClave(): array
{
    return enviarPeticionApi(
        'GET',
        '/api/termino_clave'
    );
}

function obtenerTerminoClave(string $termino): array
{
    return enviarPeticionApi(
        'GET',
        '/api/termino_clave/' . rawurlencode($termino)
    );
}

function crearTerminoClave(array $datos): array
{
    return enviarPeticionApi(
        'POST',
        '/api/termino_clave',
        $datos
    );
}

function reemplazarTerminoClave(
    string $termino,
    array $datos
): array {
    return enviarPeticionApi(
        'PUT',
        '/api/termino_clave/' . rawurlencode($termino),
        $datos
    );
}

function actualizarTerminoClave(
    string $termino,
    array $datos
): array {
    return enviarPeticionApi(
        'PATCH',
        '/api/termino_clave/' . rawurlencode($termino),
        $datos
    );
}

function retirarTerminoClave(string $termino): array
{
    return enviarPeticionApi(
        'DELETE',
        '/api/termino_clave/' . rawurlencode($termino)
    );
}