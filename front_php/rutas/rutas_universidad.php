<?php

declare(strict_types=1);

/**
 * Rutas específicas de universidad.
 * Se carga desde index.php; $ruta y $metodo se definen allí.
 * Conserva las operaciones de la v1 sin cambiar sus contratos.
 */
require_once __DIR__ . '/../vistas/universidad/lista.php';
require_once __DIR__ . '/../vistas/universidad/formulario.php';

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
