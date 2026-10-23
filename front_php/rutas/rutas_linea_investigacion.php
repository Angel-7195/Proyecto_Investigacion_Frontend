<?php

declare(strict_types=1);

/**
 * Rutas específicas de linea_investigacion.
 * Se carga desde index.php; $ruta y $metodo se definen allí.
 * Conserva las operaciones de la v1 sin cambiar sus contratos.
 */
require_once __DIR__ . '/../vistas/linea_investigacion/lista.php';
require_once __DIR__ . '/../vistas/linea_investigacion/formulario.php';

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
