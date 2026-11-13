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

// Endurece la cookie de sesión antes de iniciar la sesión PHP.
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/cliente_api.php';
require_once __DIR__ . '/vistas/inicio.php';
require_once __DIR__ . '/vistas/compartidas/no_encontrada.php';
require_once __DIR__ . '/vistas/autenticacion/login.php';

// ----------------------------------------------------------------------
// AUTENTICACIÓN — V3
// ----------------------------------------------------------------------
// ----------------------------------------------------------------------
// CERRAR SESION - V3
// ----------------------------------------------------------------------

if ($ruta === '/logout') {

    // El cierre de sesión solamente acepta POST.
    if ($metodo !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit;
    }

    // Verificar el token CSRF del formulario.
    $csrfRecibido = $_POST['csrf_token'] ?? null;
    $csrfGuardado = $_SESSION['csrf_logout'] ?? null;

    if (
        !is_string($csrfRecibido) ||
        !is_string($csrfGuardado) ||
        !hash_equals($csrfGuardado, $csrfRecibido)
    ) {
        http_response_code(403);
        exit('Solicitud no autorizada.');
    }

    // Eliminar el JWT y los datos del usuario.
    $_SESSION = [];

    // Eliminar la cookie de sesión del navegador.
    if (ini_get('session.use_cookies')) {
        $parametros = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $parametros['path'],
            'domain' => $parametros['domain'],
            'secure' => $parametros['secure'],
            'httponly' => $parametros['httponly'],
            'samesite' => $parametros['samesite'] ?? 'Lax',
        ]);
    }

    // Destruir la sesión PHP.
    session_destroy();

    // Regresar al formulario de login.
    header('Location: /login', true, 303);
    exit;
}

if ($ruta === '/login') {
    if ($metodo === 'GET') {
        if (!empty($_SESSION['jwt'])) {
            header('Location: /', true, 302);
            exit;
        }
        $_SESSION['csrf_login'] ??= bin2hex(random_bytes(32));
        mostrarLogin();
        exit;
    }

    if ($metodo === 'POST') {
        $csrfRecibido = $_POST['csrf_token'] ?? null;
        $csrfGuardado = $_SESSION['csrf_login'] ?? null;

        if (
            !is_string($csrfRecibido) || !is_string($csrfGuardado)
            || !hash_equals($csrfGuardado, $csrfRecibido)
        ) {
            http_response_code(400);
            mostrarLogin('Formulario no válido. Actualiza la página e inténtalo nuevamente.');
            exit;
        }

        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;
        if (
            !is_string($username) || !is_string($password)
            || trim($username) === '' || $password === ''
        ) {
            http_response_code(422);
            mostrarLogin('Debes ingresar usuario y contraseña.');
            exit;
        }

        $respuesta = iniciarSesionApi(trim($username), $password);
        $datos = $respuesta['datos']['datos'] ?? null;
        $usuario = is_array($datos) ? ($datos['usuario'] ?? null) : null;
        $token = is_array($datos) ? ($datos['token'] ?? null) : null;
        $roles = is_array($usuario) ? ($usuario['roles'] ?? null) : null;

        if (
            $respuesta['correcta'] && is_string($token) && $token !== ''
            && !preg_match('/[\r\n]/', $token)
            && is_array($usuario) && is_array($roles)
        ) {
            session_regenerate_id(true);
            $_SESSION['jwt'] = $token;
            $_SESSION['usuario'] = [
                'id' => (int) ($usuario['id'] ?? 0),
                'username' => (string) ($usuario['username'] ?? ''),
                'roles' => $roles,
            ];
            $_SESSION['jwt_expira_en'] = time() + (int) ($datos['expira_en'] ?? 3600);
            unset($_SESSION['csrf_login']);

            header('Location: /', true, 303);
            exit;
        }

        if ($respuesta['estado'] === 401) {
            http_response_code(401);
            mostrarLogin('Usuario o contraseña incorrectos.');
        } elseif (!$respuesta['disponible']) {
            http_response_code(503);
            mostrarLogin('No fue posible conectar con la API.');
        } else {
            http_response_code(502);
            mostrarLogin('No fue posible iniciar sesión. Inténtalo nuevamente.');
        }
        exit;
    }

    header('Allow: GET, POST');
    http_response_code(405);
    exit;
}

// ----------------------------------------------------------------------
// PROTECCIÓN DE RUTAS — V3
// ----------------------------------------------------------------------

$jwt = $_SESSION['jwt'] ?? null;
$expiracion = $_SESSION['jwt_expira_en'] ?? null;

$sinSesion = !is_string($jwt) || $jwt === '';
$sesionExpirada = $expiracion !== null
    && time() >= (int) $expiracion;

if ($sinSesion || $sesionExpirada) {

    // Limpiar los datos de autenticación.
    unset(
        $_SESSION['jwt'],
        $_SESSION['usuario'],
        $_SESSION['jwt_expira_en']
    );

    if ($metodo === 'GET' || $metodo === 'HEAD') {
        header('Location: /login', true, 302);
        exit;
    }

    http_response_code(401);
    exit('Debes iniciar sesión para realizar esta operación.');
}

// ----------------------------------------------------------------------
// AUTORIZACIÓN DE OPERACIONES — V3
// ----------------------------------------------------------------------

$roles = $_SESSION['usuario']['roles'] ?? [];

if (!is_array($roles)) {
    $roles = [];
}

$esAdmin = in_array('admin', $roles, true);

// Solamente los administradores pueden modificar registros.
if (
    !$esAdmin &&
    !in_array($metodo, ['GET', 'HEAD'], true)
) {
    http_response_code(403);
    exit('Acceso denegado. No tienes permisos para modificar registros.');
}

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

// ----------------------------------------------------------------------
// FORMULARIOS ADMINISTRATIVOS — AUTORIZACIÓN CENTRALIZADA V3
// ----------------------------------------------------------------------
// Las entidades v1/v2 utilizan /nuevo y /editar para sus formularios.
// Las rutas GET de listado y los detalles /ver siguen disponibles para consulta.
// No reemplaza la autorización del backend: la API valida cada petición.
if (!$esAdmin && $metodo === 'GET') {
    foreach ($rutasPanel as $rutaBase) {
        if ($ruta === $rutaBase . '/nuevo' || $ruta === $rutaBase . '/editar') {
            http_response_code(403);
            mostrarNoEncontrada(
                'Acceso denegado',
                'No tienes permisos para abrir formularios de creación o edición.'
            );
            exit;
        }
    }
}

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
// ADMINISTRACIÓN DE USUARIOS — V3
// ----------------------------------------------------------------------

require __DIR__ . '/rutas/rutas_usuario.php';

// ----------------------------------------------------------------------
// ASIGNACIONES DE ROLES A USUARIOS — V3
// ----------------------------------------------------------------------
require __DIR__ . '/rutas/rutas_rol_usuario.php';

// ----------------------------------------------------------------------
// RUTA NO ENCONTRADA
// ----------------------------------------------------------------------

http_response_code(404);

mostrarNoEncontrada(
    'Página no encontrada',
    'La página que intenta consultar no está disponible.'
);
