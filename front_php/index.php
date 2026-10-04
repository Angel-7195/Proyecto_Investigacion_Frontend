<?php
/**
 * Enrutador integrado del frontend de Investigación v1.
 * Mantiene el panel de consulta de seis entidades y recupera los
 * formularios CRUD de termino_clave, universidad y linea_investigacion.
 * Las consultas a datos se realizan solo mediante cliente_api.php.
 */
declare(strict_types=1);

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = rtrim(
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
    '/'
) ?: '/';

// El servidor PHP debe servir directamente los archivos de publico/.
// Comprobamos la ruta real para no exponer archivos fuera de esta carpeta.
if (PHP_SAPI === 'cli-server') {
    $rutaSolicitada = parse_url(
        $_SERVER['REQUEST_URI'] ?? '/',
        PHP_URL_PATH
    ) ?: '/';

    $archivo = realpath(__DIR__ . rawurldecode($rutaSolicitada));
    $directorioPublico = realpath(__DIR__ . '/publico');

    if (
        $archivo !== false
        && $directorioPublico !== false
        && str_starts_with(
            $archivo,
            $directorioPublico . DIRECTORY_SEPARATOR
        )
        && is_file($archivo)
    ) {
        return false;
    }
}

session_start();

require_once __DIR__ . '/cliente_api.php';
require_once __DIR__ . '/vistas/inicio.php';
require_once __DIR__ . '/vistas/lista.php';
require_once __DIR__ . '/vistas/formulario.php';
require_once __DIR__ . '/vistas/no_encontrada.php';

$recursosPanel = [
    'area_conocimiento' => 'Áreas de conocimiento',
    'objetivo_desarrollo_sostenible' => 'Objetivos de Desarrollo Sostenible',
    'area_aplicacion' => 'Áreas de aplicación',
    'termino_clave' => 'Términos clave',
    'universidad' => 'Universidades',
    'linea_investigacion' => 'Líneas de investigación',
];

// Solo las tres entidades propias tienen pantallas CRUD integradas aquí.
$rutasCrud = [
    'termino_clave' => '/terminos-clave',
    'universidad' => '/universidades',
    'linea_investigacion' => '/lineas-investigacion',
];

function escapar(mixed $valor): string
{
    if (is_array($valor) || is_object($valor)) {
        $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
    }

    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// ----------------------------------------------------------------------
// PANEL GENERAL: conserva la consulta de las seis entidades.
// ----------------------------------------------------------------------
if (($ruta === '/' || $ruta === '/index.php') && $metodo === 'GET') {
    $recurso = $_GET['recurso'] ?? '';
    $recurso = is_string($recurso) ? $recurso : '';
    $nombreRecurso = $recursosPanel[$recurso] ?? '';

    // Para los recursos que tienen CRUD, ir al listado especializado.
    if ($nombreRecurso !== '' && isset($rutasCrud[$recurso])) {
        header('Location: ' . $rutasCrud[$recurso], true, 302);
        exit;
    }

    $datos = [];
    $error = '';

    if ($nombreRecurso !== '') {
        $respuesta = llamarApi('GET', $recurso);
        $codigo = (int) ($respuesta['codigo'] ?? 0);

        if ($codigo >= 200 && $codigo < 300) {
            $contenido = $respuesta['datos'] ?? [];
            $datos = is_array($contenido)
                ? ($contenido['datos'] ?? [])
                : [];

            if (!is_array($datos)) {
                $datos = [];
            }
        } else {
            $error = ($respuesta['error'] ?? '')
                ?: 'No fue posible consultar la API.';
        }
    }
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Proyecto de Investigación</title>
        <link rel="stylesheet" href="/publico/bootstrap.min.css">
    </head>
    <body class="bg-light">
        <nav class="navbar navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="/">Proyecto de Investigación</a>
            </div>
        </nav>

        <main class="container py-4">
            <?php if ($nombreRecurso === ''): ?>
                <h1 class="mb-2">Panel de investigación</h1>
                <p class="text-secondary">
                    Selecciona el recurso que deseas consultar o administrar.
                </p>
                <div class="row g-3">
                    <?php foreach ($recursosPanel as $clave => $nombre): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <h2 class="h5"><?= escapar($nombre) ?></h2>
                                    <a
                                        class="btn btn-primary"
                                        href="/?recurso=<?= rawurlencode($clave) ?>"
                                    >
                                        <?= isset($rutasCrud[$clave]) ? 'Gestionar' : 'Consultar' ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <a href="/" class="btn btn-outline-secondary mb-3">← Volver</a>
                <h1 class="h3 mb-3"><?= escapar($nombreRecurso) ?></h1>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-warning"><?= escapar($error) ?></div>
                <?php elseif (count($datos) === 0): ?>
                    <div class="alert alert-info">No hay registros para mostrar.</div>
                <?php else: ?>
                    <p class="text-secondary">
                        Total de registros: <?= count($datos) ?>
                    </p>
                    <div class="table-responsive bg-white rounded shadow-sm">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <?php foreach (array_keys((array) $datos[0]) as $columna): ?>
                                        <th><?= escapar(ucfirst(str_replace('_', ' ', (string) $columna))) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos as $fila): ?>
                                    <tr>
                                        <?php foreach ((array) $fila as $valor): ?>
                                            <td><?= escapar($valor) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
        <script src="/publico/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// ======================================================================
// TERMINOS CLAVE
// ======================================================================

// ----------------------------------------------------------------------
// TERMINOS CLAVE — LISTAR
// ----------------------------------------------------------------------

if ($ruta === '/terminos-clave' && $metodo === 'GET') {
    mostrarListaTerminosClave();
    exit;
}

// ----------------------------------------------------------------------
// TERMINOS CLAVE — FORMULARIO DE CREACION
// ----------------------------------------------------------------------

if ($ruta === '/terminos-clave/nuevo' && $metodo === 'GET') {
    mostrarFormularioTerminoClave('crear');
    exit;
}

// ----------------------------------------------------------------------
// TERMINOS CLAVE — FORMULARIO DE EDICION
// ----------------------------------------------------------------------

if ($ruta === '/terminos-clave/editar' && $metodo === 'GET') {
    $termino = isset($_GET['termino'])
        ? trim((string) $_GET['termino'])
        : '';

    if ($termino === '') {
        http_response_code(400);

        mostrarNoEncontrada(
            'Solicitud incompleta',
            'No se indicó el término que desea editar.'
        );

        exit;
    }

    $respuesta = obtenerTerminoClave($termino);

    if (!$respuesta['disponible']) {
        http_response_code(503);

        mostrarFormularioTerminoClave(
            'editar',
            [
                'termino' => $termino,
            ],
            [
                'El servicio no está disponible en este momento.',
            ]
        );

        exit;
    }

    if (!$respuesta['correcta']) {
        if ($respuesta['estado'] === 404) {
            http_response_code(404);

            mostrarNoEncontrada(
                'Término no encontrado',
                'El término solicitado no está disponible.'
            );

            exit;
        }

        http_response_code(500);

        mostrarFormularioTerminoClave(
            'editar',
            [
                'termino' => $termino,
            ],
            [
                'No fue posible consultar el término en este momento.',
            ]
        );

        exit;
    }

    mostrarFormularioTerminoClave(
        'editar',
        $respuesta['datos']
    );

    exit;
}

// ----------------------------------------------------------------------
// TERMINOS CLAVE — CREAR
// ----------------------------------------------------------------------

if ($ruta === '/terminos-clave/crear' && $metodo === 'POST') {
    $termino = trim(
        (string) ($_POST['termino'] ?? '')
    );

    $terminoInglesTexto = trim(
        (string) ($_POST['termino_ingles'] ?? '')
    );

    $valores = [
        'termino' => $termino,
        'termino_ingles' => $terminoInglesTexto,
    ];

    $datos = [
        'termino' => $termino,
    ];

    /*
     * En creación termino_ingles es opcional.
     * Si está vacío no se envía a la API.
     */
    if ($terminoInglesTexto !== '') {
        $datos['termino_ingles'] = $terminoInglesTexto;
    }

    $respuesta = crearTerminoClave($datos);

    if (!$respuesta['disponible']) {
        mostrarFormularioTerminoClave(
            'crear',
            $valores,
            [
                'El servicio no está disponible en este momento.',
            ]
        );

        exit;
    }

    if ($respuesta['correcta']) {
        header('Location: /terminos-clave');
        exit;
    }

    if ($respuesta['estado'] === 422) {
        $errores = $respuesta['datos']['errores']
            ?? ['Revise la información ingresada.'];

        mostrarFormularioTerminoClave(
            'crear',
            $valores,
            $errores
        );

        exit;
    }

    if ($respuesta['estado'] === 409) {
        mostrarFormularioTerminoClave(
            'crear',
            $valores,
            [
                'Ya existe un término clave con ese término.',
            ]
        );

        exit;
    }

    mostrarFormularioTerminoClave(
        'crear',
        $valores,
        [
            'No fue posible registrar el término clave.',
        ]
    );

    exit;
}

// ----------------------------------------------------------------------
// TERMINOS CLAVE — ACTUALIZAR
// ----------------------------------------------------------------------

if ($ruta === '/terminos-clave/actualizar' && $metodo === 'POST') {
    $termino = trim(
        (string) ($_POST['termino'] ?? '')
    );

    $terminoInglesTexto = trim(
        (string) ($_POST['termino_ingles'] ?? '')
    );

    /*
     * En PUT y PATCH una traducción vacía se representa
     * mediante null.
     */
    $terminoIngles = $terminoInglesTexto === ''
        ? null
        : $terminoInglesTexto;

    $valores = [
        'termino' => $termino,
        'termino_ingles' => $terminoIngles,
    ];

    $accion = (string) ($_POST['accion'] ?? '');

    if ($accion === 'completo') {
        $respuesta = reemplazarTerminoClave(
            $termino,
            [
                'termino_ingles' => $terminoIngles,
            ]
        );
    } elseif ($accion === 'parcial') {
        $originalTexto = trim(
            (string) ($_POST['termino_ingles_original'] ?? '')
        );

        $original = $originalTexto === ''
            ? null
            : $originalTexto;

        if ($terminoIngles === $original) {
            mostrarFormularioTerminoClave(
                'editar',
                $valores,
                [
                    'No realizó ningún cambio.',
                ]
            );

            exit;
        }

        $respuesta = actualizarTerminoClave(
            $termino,
            [
                'termino_ingles' => $terminoIngles,
            ]
        );
    } else {
        mostrarFormularioTerminoClave(
            'editar',
            $valores,
            [
                'No se pudo determinar cómo guardar los cambios.',
            ]
        );

        exit;
    }

    if (!$respuesta['disponible']) {
        mostrarFormularioTerminoClave(
            'editar',
            $valores,
            [
                'El servicio no está disponible en este momento.',
            ]
        );

        exit;
    }

    if ($respuesta['correcta']) {
        header('Location: /terminos-clave');
        exit;
    }

    if ($respuesta['estado'] === 422) {
        $errores = $respuesta['datos']['errores']
            ?? ['Revise la información ingresada.'];

        mostrarFormularioTerminoClave(
            'editar',
            $valores,
            $errores
        );

        exit;
    }

    if ($respuesta['estado'] === 404) {
        http_response_code(404);

        mostrarNoEncontrada(
            'Término no encontrado',
            'El término que intenta modificar ya no está disponible.'
        );

        exit;
    }

    mostrarFormularioTerminoClave(
        'editar',
        $valores,
        [
            'No fue posible guardar los cambios.',
        ]
    );

    exit;
}

// ----------------------------------------------------------------------
// TERMINOS CLAVE — RETIRAR
// ----------------------------------------------------------------------

if ($ruta === '/terminos-clave/retirar' && $metodo === 'POST') {
    $termino = trim(
        (string) ($_POST['termino'] ?? '')
    );

    if ($termino === '') {
        header('Location: /terminos-clave');
        exit;
    }

    $respuesta = retirarTerminoClave($termino);

    if (!$respuesta['disponible']) {
        http_response_code(503);

        mostrarNoEncontrada(
            'Servicio no disponible',
            'No fue posible retirar el término en este momento.'
        );

        exit;
    }

    if ($respuesta['correcta']) {
        header('Location: /terminos-clave');
        exit;
    }

    if ($respuesta['estado'] === 404) {
        http_response_code(404);

        mostrarNoEncontrada(
            'Término no encontrado',
            'El término que intenta retirar ya no está disponible.'
        );

        exit;
    }

    http_response_code(500);

    mostrarNoEncontrada(
        'No fue posible completar la operación',
        'Ocurrió un problema al intentar retirar el término.'
    );

    exit;
}

// ======================================================================
// UNIVERSIDADES
// ======================================================================

// ----------------------------------------------------------------------
// UNIVERSIDADES — LISTAR
// ----------------------------------------------------------------------

if ($ruta === '/universidades' && $metodo === 'GET') {
    mostrarListaUniversidades();
    exit;
}

// ----------------------------------------------------------------------
// UNIVERSIDADES — FORMULARIO DE CREACION
// ----------------------------------------------------------------------

if ($ruta === '/universidades/nuevo' && $metodo === 'GET') {
    mostrarFormularioUniversidad('crear');
    exit;
}

// ----------------------------------------------------------------------
// UNIVERSIDADES — FORMULARIO DE EDICION
// ----------------------------------------------------------------------

if ($ruta === '/universidades/editar' && $metodo === 'GET') {
    $idTexto = trim(
        (string) ($_GET['id'] ?? '')
    );

    $id = filter_var(
        $idTexto,
        FILTER_VALIDATE_INT
    );

    if ($id === false) {
        http_response_code(400);

        mostrarNoEncontrada(
            'Solicitud incompleta',
            'No se indicó una universidad válida para editar.'
        );

        exit;
    }

    $respuesta = obtenerUniversidad($id);

    if (!$respuesta['disponible']) {
        http_response_code(503);

        mostrarFormularioUniversidad(
            'editar',
            [
                'id' => $id,
            ],
            [
                'El servicio no está disponible en este momento.',
            ]
        );

        exit;
    }

    if (!$respuesta['correcta']) {
        if ($respuesta['estado'] === 404) {
            http_response_code(404);

            mostrarNoEncontrada(
                'Universidad no encontrada',
                'La universidad solicitada no está disponible.'
            );

            exit;
        }

        http_response_code(500);

        mostrarFormularioUniversidad(
            'editar',
            [
                'id' => $id,
            ],
            [
                'No fue posible consultar la universidad en este momento.',
            ]
        );

        exit;
    }

    mostrarFormularioUniversidad(
        'editar',
        $respuesta['datos']
    );

    exit;
}

// ----------------------------------------------------------------------
// UNIVERSIDADES — CREAR
// ----------------------------------------------------------------------

if ($ruta === '/universidades/crear' && $metodo === 'POST') {
    $idTexto = trim(
        (string) ($_POST['id'] ?? '')
    );

    $nombre = trim(
        (string) ($_POST['nombre'] ?? '')
    );

    $tipo = trim(
        (string) ($_POST['tipo'] ?? '')
    );

    $ciudad = trim(
        (string) ($_POST['ciudad'] ?? '')
    );

    $valores = [
        'id' => $idTexto,
        'nombre' => $nombre,
        'tipo' => $tipo,
        'ciudad' => $ciudad,
    ];

    $id = filter_var(
        $idTexto,
        FILTER_VALIDATE_INT
    );

    if ($id === false) {
        mostrarFormularioUniversidad(
            'crear',
            $valores,
            [
                'El campo id debe ser un entero.',
            ]
        );

        exit;
    }

    $respuesta = crearUniversidad(
        [
            'id' => $id,
            'nombre' => $nombre,
            'tipo' => $tipo,
            'ciudad' => $ciudad,
        ]
    );

    if (!$respuesta['disponible']) {
        mostrarFormularioUniversidad(
            'crear',
            $valores,
            [
                'El servicio no está disponible en este momento.',
            ]
        );

        exit;
    }

    if ($respuesta['correcta']) {
        header('Location: /universidades');
        exit;
    }

    if ($respuesta['estado'] === 422) {
        $errores = $respuesta['datos']['errores']
            ?? ['Revise la información ingresada.'];

        mostrarFormularioUniversidad(
            'crear',
            $valores,
            $errores
        );

        exit;
    }

    if ($respuesta['estado'] === 409) {
        mostrarFormularioUniversidad(
            'crear',
            $valores,
            [
                'Ya existe una universidad con ese ID.',
            ]
        );

        exit;
    }

    mostrarFormularioUniversidad(
        'crear',
        $valores,
        [
            'No fue posible registrar la universidad.',
        ]
    );

    exit;
}

// ----------------------------------------------------------------------
// UNIVERSIDADES — ACTUALIZAR
// ----------------------------------------------------------------------

if ($ruta === '/universidades/actualizar' && $metodo === 'POST') {
    $idTexto = trim(
        (string) ($_POST['id'] ?? '')
    );

    $id = filter_var(
        $idTexto,
        FILTER_VALIDATE_INT
    );

    if ($id === false) {
        http_response_code(400);

        mostrarNoEncontrada(
            'Solicitud incompleta',
            'No se indicó una universidad válida.'
        );

        exit;
    }

    $nombre = trim(
        (string) ($_POST['nombre'] ?? '')
    );

    $tipo = trim(
        (string) ($_POST['tipo'] ?? '')
    );

    $ciudad = trim(
        (string) ($_POST['ciudad'] ?? '')
    );

    $valores = [
        'id' => $id,
        'nombre' => $nombre,
        'tipo' => $tipo,
        'ciudad' => $ciudad,
    ];

    $accion = (string) ($_POST['accion'] ?? '');

    if ($accion === 'completo') {
        /*
         * PUT reemplaza la ficha completa.
         * El id no se envía porque identifica el recurso en la ruta
         * y no puede modificarse.
         */
        $respuesta = reemplazarUniversidad(
            $id,
            [
                'nombre' => $nombre,
                'tipo' => $tipo,
                'ciudad' => $ciudad,
            ]
        );
    } elseif ($accion === 'parcial') {
        /*
         * PATCH envía únicamente los campos que realmente
         * fueron modificados por el usuario.
         */
        $nombreOriginal = trim(
            (string) ($_POST['nombre_original'] ?? '')
        );

        $tipoOriginal = trim(
            (string) ($_POST['tipo_original'] ?? '')
        );

        $ciudadOriginal = trim(
            (string) ($_POST['ciudad_original'] ?? '')
        );

        $datos = [];

        if ($nombre !== $nombreOriginal) {
            $datos['nombre'] = $nombre;
        }

        if ($tipo !== $tipoOriginal) {
            $datos['tipo'] = $tipo;
        }

        if ($ciudad !== $ciudadOriginal) {
            $datos['ciudad'] = $ciudad;
        }

        /*
         * Evitamos enviar un PATCH vacío desde la pantalla.
         * La API también protege este caso y respondería 400.
         */
        if ($datos === []) {
            mostrarFormularioUniversidad(
                'editar',
                $valores,
                [
                    'No realizó ningún cambio.',
                ]
            );

            exit;
        }

        $respuesta = actualizarUniversidad(
            $id,
            $datos
        );
    } else {
        mostrarFormularioUniversidad(
            'editar',
            $valores,
            [
                'No se pudo determinar cómo guardar los cambios.',
            ]
        );

        exit;
    }

    if (!$respuesta['disponible']) {
        mostrarFormularioUniversidad(
            'editar',
            $valores,
            [
                'El servicio no está disponible en este momento.',
            ]
        );

        exit;
    }

    if ($respuesta['correcta']) {
        header('Location: /universidades');
        exit;
    }

    if ($respuesta['estado'] === 422) {
        $errores = $respuesta['datos']['errores']
            ?? ['Revise la información ingresada.'];

        mostrarFormularioUniversidad(
            'editar',
            $valores,
            $errores
        );

        exit;
    }

    if ($respuesta['estado'] === 404) {
        http_response_code(404);

        mostrarNoEncontrada(
            'Universidad no encontrada',
            'La universidad que intenta modificar ya no está disponible.'
        );

        exit;
    }

    mostrarFormularioUniversidad(
        'editar',
        $valores,
        [
            'No fue posible guardar los cambios.',
        ]
    );

    exit;
}

// ----------------------------------------------------------------------
// UNIVERSIDADES — RETIRAR
// ----------------------------------------------------------------------

if ($ruta === '/universidades/retirar' && $metodo === 'POST') {
    $idTexto = trim(
        (string) ($_POST['id'] ?? '')
    );

    $id = filter_var(
        $idTexto,
        FILTER_VALIDATE_INT
    );

    if ($id === false) {
        header('Location: /universidades');
        exit;
    }

    $respuesta = retirarUniversidad($id);

    if (!$respuesta['disponible']) {
        http_response_code(503);

        mostrarNoEncontrada(
            'Servicio no disponible',
            'No fue posible retirar la universidad en este momento.'
        );

        exit;
    }

    if ($respuesta['correcta']) {
        header('Location: /universidades');
        exit;
    }

    if ($respuesta['estado'] === 404) {
        http_response_code(404);

        mostrarNoEncontrada(
            'Universidad no encontrada',
            'La universidad que intenta retirar ya no está disponible.'
        );

        exit;
    }

    http_response_code(500);

    mostrarNoEncontrada(
        'No fue posible completar la operación',
        'Ocurrió un problema al intentar retirar la universidad.'
    );

    exit;
}

// ======================================================================
// LINEAS DE INVESTIGACION
// ======================================================================

// ----------------------------------------------------------------------
// LISTAR
// ----------------------------------------------------------------------

if ($ruta === '/lineas-investigacion' && $metodo === 'GET') {
    mostrarListaLineasInvestigacion();
    exit;
}

// ----------------------------------------------------------------------
// FORMULARIO DE CREACION
// ----------------------------------------------------------------------

if ($ruta === '/lineas-investigacion/nuevo' && $metodo === 'GET') {
    mostrarFormularioLineaInvestigacion('crear');
    exit;
}

// ----------------------------------------------------------------------
// FORMULARIO DE EDICION
// ----------------------------------------------------------------------

if ($ruta === '/lineas-investigacion/editar' && $metodo === 'GET') {
    $id = filter_var(
        $_GET['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        http_response_code(400);
        mostrarNoEncontrada(
            'Solicitud incompleta',
            'No se indicó una línea de investigación válida para editar.'
        );
        exit;
    }

    $respuesta = obtenerLineaInvestigacion($id);

    if (!$respuesta['disponible']) {
        http_response_code(503);
        mostrarFormularioLineaInvestigacion(
            'editar',
            ['id' => $id],
            ['El servicio no está disponible en este momento.']
        );
        exit;
    }

    if (!$respuesta['correcta']) {
        if ($respuesta['estado'] === 404) {
            http_response_code(404);
            mostrarNoEncontrada(
                'Línea de investigación no encontrada',
                'La línea solicitada no está disponible.'
            );
            exit;
        }

        http_response_code(500);
        mostrarFormularioLineaInvestigacion(
            'editar',
            ['id' => $id],
            ['No fue posible consultar la línea de investigación.']
        );
        exit;
    }

    mostrarFormularioLineaInvestigacion(
        'editar',
        $respuesta['datos']
    );
    exit;
}

// ----------------------------------------------------------------------
// CREAR — sin id, porque la API lo genera mediante AUTO_INCREMENT
// ----------------------------------------------------------------------

if ($ruta === '/lineas-investigacion/crear' && $metodo === 'POST') {
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $descripcion = trim((string) ($_POST['descripcion'] ?? ''));

    $valores = [
        'nombre' => $nombre,
        'descripcion' => $descripcion,
    ];

    $respuesta = crearLineaInvestigacion($valores);

    if (!$respuesta['disponible']) {
        mostrarFormularioLineaInvestigacion(
            'crear',
            $valores,
            ['El servicio no está disponible en este momento.']
        );
        exit;
    }

    if ($respuesta['correcta']) {
        header('Location: /lineas-investigacion');
        exit;
    }

    if ($respuesta['estado'] === 422) {
        mostrarFormularioLineaInvestigacion(
            'crear',
            $valores,
            $respuesta['datos']['errores']
                ?? ['Revise la información ingresada.']
        );
        exit;
    }

    mostrarFormularioLineaInvestigacion(
        'crear',
        $valores,
        ['No fue posible registrar la línea de investigación.']
    );
    exit;
}

// ----------------------------------------------------------------------
// ACTUALIZAR — PUT completo / PATCH de los campos modificados
// ----------------------------------------------------------------------

if ($ruta === '/lineas-investigacion/actualizar' && $metodo === 'POST') {
    $id = filter_var(
        $_POST['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        http_response_code(400);
        mostrarNoEncontrada(
            'Solicitud incompleta',
            'No se indicó una línea de investigación válida.'
        );
        exit;
    }

    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $descripcion = trim((string) ($_POST['descripcion'] ?? ''));

    $valores = [
        'id' => $id,
        'nombre' => $nombre,
        'descripcion' => $descripcion,
    ];

    $accion = (string) ($_POST['accion'] ?? '');

    if ($accion === 'completo') {
        // El id identifica la ficha en la URL; nunca se envía en el cuerpo.
        $respuesta = reemplazarLineaInvestigacion(
            $id,
            [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
            ]
        );
    } elseif ($accion === 'parcial') {
        $nombreOriginal = trim(
            (string) ($_POST['nombre_original'] ?? '')
        );
        $descripcionOriginal = trim(
            (string) ($_POST['descripcion_original'] ?? '')
        );

        $datos = [];

        if ($nombre !== $nombreOriginal) {
            $datos['nombre'] = $nombre;
        }

        if ($descripcion !== $descripcionOriginal) {
            $datos['descripcion'] = $descripcion;
        }

        if ($datos === []) {
            mostrarFormularioLineaInvestigacion(
                'editar',
                $valores,
                ['No realizó ningún cambio.']
            );
            exit;
        }

        $respuesta = actualizarLineaInvestigacion($id, $datos);
    } else {
        mostrarFormularioLineaInvestigacion(
            'editar',
            $valores,
            ['No se pudo determinar cómo guardar los cambios.']
        );
        exit;
    }

    if (!$respuesta['disponible']) {
        mostrarFormularioLineaInvestigacion(
            'editar',
            $valores,
            ['El servicio no está disponible en este momento.']
        );
        exit;
    }

    if ($respuesta['correcta']) {
        header('Location: /lineas-investigacion');
        exit;
    }

    if ($respuesta['estado'] === 422) {
        mostrarFormularioLineaInvestigacion(
            'editar',
            $valores,
            $respuesta['datos']['errores']
                ?? ['Revise la información ingresada.']
        );
        exit;
    }

    if ($respuesta['estado'] === 404) {
        http_response_code(404);
        mostrarNoEncontrada(
            'Línea de investigación no encontrada',
            'La línea que intenta modificar ya no está disponible.'
        );
        exit;
    }

    mostrarFormularioLineaInvestigacion(
        'editar',
        $valores,
        ['No fue posible guardar los cambios.']
    );
    exit;
}

// ----------------------------------------------------------------------
// RETIRAR — borrado lógico mediante la API
// ----------------------------------------------------------------------

if ($ruta === '/lineas-investigacion/retirar' && $metodo === 'POST') {
    $id = filter_var(
        $_POST['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        header('Location: /lineas-investigacion');
        exit;
    }

    $respuesta = retirarLineaInvestigacion($id);

    if (!$respuesta['disponible']) {
        http_response_code(503);
        mostrarNoEncontrada(
            'Servicio no disponible',
            'No fue posible retirar la línea de investigación.'
        );
        exit;
    }

    if ($respuesta['correcta']) {
        header('Location: /lineas-investigacion');
        exit;
    }

    if ($respuesta['estado'] === 404) {
        http_response_code(404);
        mostrarNoEncontrada(
            'Línea de investigación no encontrada',
            'La línea que intenta retirar ya no está disponible.'
        );
        exit;
    }

    http_response_code(500);
    mostrarNoEncontrada(
        'No fue posible completar la operación',
        'Ocurrió un problema al intentar retirar la línea de investigación.'
    );
    exit;
}

// ----------------------------------------------------------------------
// RUTA NO ENCONTRADA
// ----------------------------------------------------------------------

http_response_code(404);

mostrarNoEncontrada(
    'Página no encontrada',
    'La página que intenta consultar no está disponible.'
);
