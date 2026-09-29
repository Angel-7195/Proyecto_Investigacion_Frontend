<?php

/**
 * index.php — Front Controller del frontend de Investigación v1.
 */

declare(strict_types=1);

$metodo = $_SERVER['REQUEST_METHOD'];

$ruta = rtrim(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
    '/'
) ?: '/';

// Los archivos estáticos deben ser servidos directamente por PHP.
if (PHP_SAPI === 'cli-server') {
    $archivo = __DIR__ . $ruta;

    if ($ruta !== '/' && is_file($archivo)) {
        return false;
    }
}

session_start();

require_once __DIR__ . '/cliente_api.php';
require_once __DIR__ . '/vistas/inicio.php';
require_once __DIR__ . '/vistas/lista.php';
require_once __DIR__ . '/vistas/formulario.php';
require_once __DIR__ . '/vistas/no_encontrada.php';


// ----------------------------------------------------------------------
// INICIO
// ----------------------------------------------------------------------

if ($ruta === '/' && $metodo === 'GET') {
    mostrarInicio();
    exit;
}


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


// ----------------------------------------------------------------------
// RUTA NO ENCONTRADA
// ----------------------------------------------------------------------

http_response_code(404);

mostrarNoEncontrada(
    'Página no encontrada',
    'La página que intenta consultar no está disponible.'
);