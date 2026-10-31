<?php
/**
 * Front Controller del frontend de Investigación.
 * Conserva las rutas de V1 e incorpora las entidades V2 y las asociaciones ac_linea, ods_linea, aa_linea , grupo_linea y semillero_linea.
 * Las rutas HTTP públicas y los formularios permanecen sin cambios.
 */
declare(strict_types=1);

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = rtrim(
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
    '/'
) ?: '/';

// El servidor PHP entrega directamente los archivos de publico/.
if (PHP_SAPI === 'cli-server') {
    $rutaSolicitada = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $archivo = realpath(__DIR__ . rawurldecode($rutaSolicitada));
    $directorioPublico = realpath(__DIR__ . '/publico');
    if (
        $archivo !== false
        && $directorioPublico !== false
        && str_starts_with($archivo, $directorioPublico . DIRECTORY_SEPARATOR)
        && is_file($archivo)
    ) {
        return false;
    }
}

session_start();

require_once __DIR__ . '/cliente_api.php';
require_once __DIR__ . '/vistas/inicio.php';
require_once __DIR__ . '/vistas/compartidas/no_encontrada.php';

// Mantiene los enlaces antiguos del panel: /?recurso=area_conocimiento, etc.
$rutasPanel = [
    'area_conocimiento' => '/areas-conocimiento',
    'objetivo_desarrollo_sostenible' => '/objetivos-desarrollo-sostenible',
    'area_aplicacion' => '/areas-aplicacion',
    'termino_clave' => '/terminos-clave',
    'universidad' => '/universidades',
    'linea_investigacion' => '/lineas-investigacion',
    'grupo_investigacion' => '/grupos-investigacion',
    'semillero' => '/semilleros',
    'docente' => '/docentes',
    'participa_grupo' => '/participaciones-grupo',
    'participa_semillero' => '/participaciones-semillero',
    'ac_linea' => '/asociaciones-ac-linea',
    'ods_linea' => '/asociaciones-ods-linea',
    'aa_linea' => '/asociaciones-aa-linea',
    'grupo_linea' => '/asociaciones-grupo-linea',
    'semillero_linea' => '/asociaciones-semillero-linea',
];

if (($ruta === '/' || $ruta === '/index.php') && $metodo === 'GET') {
    $recurso = $_GET['recurso'] ?? '';
    if (is_string($recurso) && isset($rutasPanel[$recurso])) {
        header('Location: ' . $rutasPanel[$recurso], true, 302);
        exit;
    }
    mostrarInicio();
    exit;
}

// Cada archivo de rutas procesa solamente su entidad y regresa si no coincide.
// Se conserva el mismo orden de resolución que tenía la V1.
require __DIR__ . '/rutas/rutas_area_conocimiento.php';
require __DIR__ . '/rutas/rutas_objetivo_desarrollo_sostenible.php';
require __DIR__ . '/rutas/rutas_area_aplicacion.php';
require __DIR__ . '/rutas/rutas_termino_clave.php';
require __DIR__ . '/rutas/rutas_universidad.php';
require __DIR__ . '/rutas/rutas_linea_investigacion.php';
require __DIR__ . '/rutas/rutas_grupo_investigacion.php';
require __DIR__ . '/rutas/rutas_semillero.php';
require __DIR__ . '/rutas/rutas_docente.php';
require __DIR__ . '/rutas/rutas_participa_grupo.php';
require __DIR__ . '/rutas/rutas_participa_semillero.php';
require __DIR__ . '/rutas/rutas_ac_linea.php';
require __DIR__ . '/rutas/rutas_ods_linea.php';
require __DIR__ . '/rutas/rutas_aa_linea.php';
require __DIR__ . '/rutas/rutas_grupo_linea.php';
require __DIR__ . '/rutas/rutas_semillero_linea.php';

// ----------------------------------------------------------------------
// RUTA NO ENCONTRADA
// ----------------------------------------------------------------------

http_response_code(404);

mostrarNoEncontrada(
    'Página no encontrada',
    'La página que intenta consultar no está disponible.'
);
