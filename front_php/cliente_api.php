<?php

declare(strict_types=1);

/**
 * Construye las URL de la API sin acceder directamente a MariaDB.
 * En Docker, URL_API debe apuntar al servicio api-investigacion.
 */
function apiUrl(string $recurso = '', string|array $id = ''): string
{
    $base = getenv('URL_API');

    if ($base === false || trim($base) === '') {
        return '';
    }

    $url = rtrim($base, '/') . '/api/' . rawurlencode($recurso);

    // Un arreglo representa segmentos individuales de una clave compuesta.
    if (is_array($id)) {
        foreach ($id as $parte) {
            $url .= '/' . rawurlencode((string) $parte);
        }
    } elseif ($id !== '') {
        $url .= '/' . rawurlencode($id);
    }

    return $url;
}

/**
 * Cliente HTTP genérico: conserva el contrato usado por el panel
 * de consulta de las entidades de Investigación.
 *
 * @return array{codigo:int, datos:array, error:string}
 */
function llamarApi(
    string $metodo,
    string $recurso,
    string|array $id = '',
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

// ----------------------------------------------------------------------
// GRUPO DE INVESTIGACION - V2
// ----------------------------------------------------------------------

function listarGruposInvestigacion(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'grupo_investigacion'));
}

function obtenerGrupoInvestigacion(int $id): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'grupo_investigacion', (string) $id));
}

function crearGrupoInvestigacion(array $datos): array
{
    return adaptarRespuestaApi(llamarApi('POST', 'grupo_investigacion', '', $datos));
}

function reemplazarGrupoInvestigacion(int $id, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PUT', 'grupo_investigacion', (string) $id, $datos));
}

function actualizarGrupoInvestigacion(int $id, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PATCH', 'grupo_investigacion', (string) $id, $datos));
}

function retirarGrupoInvestigacion(int $id): array
{
    return adaptarRespuestaApi(llamarApi('DELETE', 'grupo_investigacion', (string) $id));
}

// ----------------------------------------------------------------------
// SEMILLERO - V2
// ----------------------------------------------------------------------

function listarSemilleros(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'semillero'));
}

function obtenerSemillero(int $id): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'semillero', (string) $id));
}

function crearSemillero(array $datos): array
{
    return adaptarRespuestaApi(llamarApi('POST', 'semillero', '', $datos));
}

function reemplazarSemillero(int $id, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PUT', 'semillero', (string) $id, $datos));
}

function actualizarSemillero(int $id, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PATCH', 'semillero', (string) $id, $datos));
}

function retirarSemillero(int $id): array
{
    return adaptarRespuestaApi(llamarApi('DELETE', 'semillero', (string) $id));
}

// ----------------------------------------------------------------------
// DOCENTE - V2
// ----------------------------------------------------------------------

function listarDocentes(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'docente'));
}

function obtenerDocente(int $cedula): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'docente', (string) $cedula));
}

function crearDocente(array $datos): array
{
    return adaptarRespuestaApi(llamarApi('POST', 'docente', '', $datos));
}

function reemplazarDocente(int $cedula, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PUT', 'docente', (string) $cedula, $datos));
}

function actualizarDocente(int $cedula, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PATCH', 'docente', (string) $cedula, $datos));
}

function retirarDocente(int $cedula): array
{
    return adaptarRespuestaApi(llamarApi('DELETE', 'docente', (string) $cedula));
}

// ----------------------------------------------------------------------
// PARTICIPA GRUPO - V2 (CLAVE PRIMARIA COMPUESTA)
// ----------------------------------------------------------------------

function listarParticipacionesGrupo(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'participa_grupo'));
}

function obtenerParticipacionGrupo(int $cedula, int $grupoId): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'participa_grupo', [(string) $cedula, (string) $grupoId]));
}

function crearParticipacionGrupo(array $datos): array
{
    return adaptarRespuestaApi(llamarApi('POST', 'participa_grupo', '', $datos));
}

function reemplazarParticipacionGrupo(int $cedula, int $grupoId, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PUT', 'participa_grupo', [(string) $cedula, (string) $grupoId], $datos));
}

function actualizarParticipacionGrupo(int $cedula, int $grupoId, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PATCH', 'participa_grupo', [(string) $cedula, (string) $grupoId], $datos));
}

function retirarParticipacionGrupo(int $cedula, int $grupoId): array
{
    return adaptarRespuestaApi(llamarApi('DELETE', 'participa_grupo', [(string) $cedula, (string) $grupoId]));
}

// ----------------------------------------------------------------------
// PARTICIPA SEMILLERO - V2 (CLAVE PRIMARIA COMPUESTA)
// ----------------------------------------------------------------------

function listarParticipacionesSemillero(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'participa_semillero'));
}

function obtenerParticipacionSemillero(int $cedula, int $semilleroId): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'participa_semillero', [(string) $cedula, (string) $semilleroId]));
}

function crearParticipacionSemillero(array $datos): array
{
    return adaptarRespuestaApi(llamarApi('POST', 'participa_semillero', '', $datos));
}

function reemplazarParticipacionSemillero(int $cedula, int $semilleroId, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PUT', 'participa_semillero', [(string) $cedula, (string) $semilleroId], $datos));
}

function actualizarParticipacionSemillero(int $cedula, int $semilleroId, array $datos): array
{
    return adaptarRespuestaApi(llamarApi('PATCH', 'participa_semillero', [(string) $cedula, (string) $semilleroId], $datos));
}

function retirarParticipacionSemillero(int $cedula, int $semilleroId): array
{
    return adaptarRespuestaApi(llamarApi('DELETE', 'participa_semillero', [(string) $cedula, (string) $semilleroId]));
}


// ----------------------------------------------------------------------
// AC LINEA - V2 (CLAVE COMPUESTA: INT + VARCHAR(6))
// Solo GET, POST y DELETE. PUT/PATCH no tienen campos editables.
// ----------------------------------------------------------------------

function listarAreasConocimientoAcLinea(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'area_conocimiento'));
}

function listarAsociacionesAcLinea(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'ac_linea'));
}

function obtenerAsociacionAcLinea(int $lineaId, string $areaId): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'ac_linea', [(string) $lineaId, $areaId]));
}

function crearAsociacionAcLinea(array $datos): array
{
    return adaptarRespuestaApi(llamarApi('POST', 'ac_linea', '', $datos));
}

function retirarAsociacionAcLinea(int $lineaId, string $areaId): array
{
    return adaptarRespuestaApi(llamarApi('DELETE', 'ac_linea', [(string) $lineaId, $areaId]));
}


// ----------------------------------------------------------------------
// ODS LINEA - V2 (CLAVE COMPUESTA: INT + INT)
// Solo GET, POST y DELETE. PUT/PATCH no tienen campos editables.
// ----------------------------------------------------------------------

/** Catálogo de los 17 ODS y los que se hayan registrado posteriormente. */
function listarObjetivosDesarrolloSostenibleOdsLinea(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'objetivo_desarrollo_sostenible'));
}

function listarAsociacionesOdsLinea(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'ods_linea'));
}

function obtenerAsociacionOdsLinea(int $lineaId, int $odsId): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'ods_linea', [(string) $lineaId, (string) $odsId]));
}

function crearAsociacionOdsLinea(array $datos): array
{
    return adaptarRespuestaApi(llamarApi('POST', 'ods_linea', '', $datos));
}

function retirarAsociacionOdsLinea(int $lineaId, int $odsId): array
{
    return adaptarRespuestaApi(llamarApi('DELETE', 'ods_linea', [(string) $lineaId, (string) $odsId]));
}


// ----------------------------------------------------------------------
// AA LINEA - V2 (PK COMPUESTA: AREA_APLICACION INT + LINEA_INVESTIGACION INT)
// GET, POST y DELETE; la asociación no tiene campos editables.
// ----------------------------------------------------------------------

/** Catálogo de áreas de aplicación activas para el selector. */
function listarAreasAplicacionAaLinea(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'area_aplicacion'));
}

function listarAsociacionesAaLinea(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'aa_linea'));
}

function obtenerAsociacionAaLinea(int $areaId, int $lineaId): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'aa_linea', [(string) $areaId, (string) $lineaId]));
}

function crearAsociacionAaLinea(array $datos): array
{
    return adaptarRespuestaApi(llamarApi('POST', 'aa_linea', '', $datos));
}

function retirarAsociacionAaLinea(int $areaId, int $lineaId): array
{
    return adaptarRespuestaApi(llamarApi('DELETE', 'aa_linea', [(string) $areaId, (string) $lineaId]));
}


// ----------------------------------------------------------------------
// GRUPO LINEA - V2 (PK COMPUESTA: GRUPO_INVESTIGACION INT + LINEA_INVESTIGACION INT)
// Solo GET, POST y DELETE: las claves primarias no admiten edición.
// ----------------------------------------------------------------------

function listarAsociacionesGrupoLinea(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'grupo_linea'));
}

function obtenerAsociacionGrupoLinea(int $grupoId, int $lineaId): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'grupo_linea', [(string) $grupoId, (string) $lineaId]));
}

function crearAsociacionGrupoLinea(array $datos): array
{
    return adaptarRespuestaApi(llamarApi('POST', 'grupo_linea', '', $datos));
}

function retirarAsociacionGrupoLinea(int $grupoId, int $lineaId): array
{
    return adaptarRespuestaApi(llamarApi('DELETE', 'grupo_linea', [(string) $grupoId, (string) $lineaId]));
}


// ----------------------------------------------------------------------
// SEMILLERO LINEA - V2 (PK COMPUESTA: SEMILLERO INT + LINEA_INVESTIGACION INT)
// Solo GET, POST y DELETE; las claves primarias no son editables.
// ----------------------------------------------------------------------

function listarAsociacionesSemilleroLinea(): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'semillero_linea'));
}

function obtenerAsociacionSemilleroLinea(int $semilleroId, int $lineaId): array
{
    return adaptarRespuestaApi(llamarApi('GET', 'semillero_linea', [(string) $semilleroId, (string) $lineaId]));
}

function crearAsociacionSemilleroLinea(array $datos): array
{
    return adaptarRespuestaApi(llamarApi('POST', 'semillero_linea', '', $datos));
}

function retirarAsociacionSemilleroLinea(int $semilleroId, int $lineaId): array
{
    return adaptarRespuestaApi(llamarApi('DELETE', 'semillero_linea', [(string) $semilleroId, (string) $lineaId]));
}
