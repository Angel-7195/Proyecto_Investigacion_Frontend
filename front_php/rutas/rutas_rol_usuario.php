<?php

declare(strict_types=1);

/** Administración de asignaciones rol_usuario. No administra la tabla rol. */
if (!in_array($ruta, [
    '/roles-usuarios',
    '/roles-usuarios/asignar',
    '/roles-usuarios/confirmar-retiro',
    '/roles-usuarios/retirar',
], true)) {
    return;
}

require_once __DIR__ . '/../vistas/rol_usuario/gestion.php';

// El frontend protege la navegación; la API comprueba los permisos vigentes.
$rolesAsignacion = $_SESSION['usuario']['roles'] ?? [];
if (!is_array($rolesAsignacion) || !in_array('admin', $rolesAsignacion, true)) {
    http_response_code(403);
    renderizarPlantilla(
        'Acceso denegado',
        '<div class="alert alert-warning" role="alert">'
        . '<h1 class="h4">Acceso denegado</h1>'
        . '<p>Solo ADMIN puede gestionar las asignaciones de roles.</p>'
        . '<a href="/" class="btn btn-outline-secondary">Volver al inicio</a>'
        . '</div>'
    );
    exit;
}

header('Cache-Control: no-store, private');

// ----------------------------------------------------------------------
// LISTADO Y FORMULARIO DE ASIGNACIÓN
// ----------------------------------------------------------------------
if ($ruta === '/roles-usuarios') {
    if ($metodo !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        exit;
    }

    mostrarGestionRolUsuario(listarRolesUsuario(), listarUsuarios());
    exit;
}

// ----------------------------------------------------------------------
// POST DEL FRONTEND -> POST /api/rol_usuario (201 O 409)
// ----------------------------------------------------------------------
if ($ruta === '/roles-usuarios/asignar') {
    if ($metodo !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit;
    }

    $csrfRecibido = $_POST['csrf_token'] ?? null;
    $csrfGuardado = $_SESSION['csrf_rol_usuario'] ?? null;
    if (!is_string($csrfRecibido) || !is_string($csrfGuardado)
        || !hash_equals($csrfGuardado, $csrfRecibido)) {
        http_response_code(403);
        exit('Solicitud no autorizada.');
    }

    $usuarioId = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $rolId = filter_var($_POST['rol_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    $valores = [
        'usuario_id' => is_int($usuarioId) ? $usuarioId : 0,
        'rol_id' => is_int($rolId) ? $rolId : 0,
    ];
    $errores = [];
    $estado = 422;

    if (!is_int($usuarioId)) {
        $errores[] = 'Selecciona un usuario activo válido.';
    }
    if (!is_int($rolId) || !in_array($rolId, [1, 2], true)) {
        $errores[] = 'Selecciona un rol permitido: admin o consulta.';
    }
    // La asignación del rol admin exige un paso consciente y explícito.
    if ($rolId === 1 && ($_POST['confirmar_admin'] ?? null) !== '1') {
        $errores[] = 'Para otorgar permisos administrativos debes marcar la confirmación.';
    }

    if ($errores === []) {
        // El backend rechaza usuarios/roles inactivos y asignaciones duplicadas.
        $respuesta = asignarRolUsuario($usuarioId, $rolId);
        if ($respuesta['correcta'] && (int) $respuesta['estado'] === 201) {
            unset($_SESSION['csrf_rol_usuario']);
            $_SESSION['mensaje_rol_usuario'] = 'Rol asignado correctamente. '
                . 'El usuario deberá iniciar sesión para actualizar los roles de su interfaz.';
            header('Location: /roles-usuarios', true, 303);
            exit;
        }

        $estado = (int) $respuesta['estado'];
        if ($estado === 409) {
            $errores = ['Este usuario ya tiene asignado el rol seleccionado.'];
        } elseif ($estado === 422) {
            $detalles = $respuesta['datos']['errores'] ?? [];
            $errores = is_array($detalles) && $detalles !== []
                ? array_map('strval', $detalles)
                : [(string) ($respuesta['datos']['mensaje'] ?? 'Usuario o rol inválido o inactivo.')];
        } elseif (in_array($estado, [401, 403], true)) {
            $errores = ['Tu sesión ya no dispone de autorización para asignar roles.'];
        } else {
            $estado = $respuesta['disponible'] ? 502 : 503;
            $errores = ['No fue posible comunicarse correctamente con la API.'];
        }
    }

    http_response_code($estado);
    mostrarGestionRolUsuario(listarRolesUsuario(), listarUsuarios(), $errores, $valores);
    exit;
}

// ----------------------------------------------------------------------
// CONFIRMAR RETIRO — GET (NUNCA SE RETIRA UN ROL DESDE UN ENLACE GET)
// ----------------------------------------------------------------------
if ($ruta === '/roles-usuarios/confirmar-retiro') {
    if ($metodo !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        exit;
    }

    $usuarioId = filter_var($_GET['usuario_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $rolId = filter_var($_GET['rol_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    if (!is_int($usuarioId) || !is_int($rolId)) {
        http_response_code(400);
        renderizarPlantilla('Solicitud inválida',
            '<div class="alert alert-warning">Los identificadores no son válidos.</div>');
        exit;
    }

    // Verifica que la asociación continúe existiendo antes de confirmar.
    $asignacion = obtenerRolUsuario($usuarioId, $rolId);
    if (!$asignacion['correcta'] || (int) $asignacion['estado'] !== 200) {
        $estado = (int) $asignacion['estado'];
        http_response_code(in_array($estado, [401, 403, 404], true)
            ? $estado : ($asignacion['disponible'] ? 502 : 503));
        renderizarPlantilla('Asignación no disponible',
            '<div class="alert alert-warning">La asignación ya no existe o no se pudo consultar.</div>'
            . '<a class="btn btn-outline-secondary" href="/roles-usuarios">Volver al listado</a>');
        exit;
    }

    $consultaUsuario = obtenerUsuario($usuarioId);
    $registro = $consultaUsuario['datos']['datos'] ?? null;
    $username = $consultaUsuario['correcta'] && is_array($registro)
        ? (string) ($registro['username'] ?? '')
        : ('Usuario ID ' . $usuarioId);

    mostrarConfirmacionRetiroRolUsuario($usuarioId, $rolId, $username);
    exit;
}

// ----------------------------------------------------------------------
// RETIRO — POST DEL FRONTEND -> DELETE /api/rol_usuario/{usuario}/{rol}
// ----------------------------------------------------------------------
if ($ruta === '/roles-usuarios/retirar') {
    if ($metodo !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit;
    }

    $usuarioId = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $rolId = filter_var($_POST['rol_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    if (!is_int($usuarioId) || !is_int($rolId)) {
        http_response_code(400);
        exit('Los identificadores no son válidos.');
    }

    // Cada token CSRF está ligado a una pareja usuario/rol concreta.
    // Modificar IDs ocultos en el navegador invalida la confirmación.
    $claveSesion = $usuarioId . ':' . $rolId;
    $csrfRecibido = $_POST['csrf_token'] ?? null;
    $csrfGuardado = $_SESSION['csrf_retiro_rol_usuario'][$claveSesion] ?? null;
    if (!is_string($csrfRecibido) || !is_string($csrfGuardado)
        || !hash_equals($csrfGuardado, $csrfRecibido)) {
        http_response_code(403);
        exit('Solicitud no autorizada.');
    }

    $retiro = retirarRolUsuario($usuarioId, $rolId);
    if ($retiro['correcta'] && (int) $retiro['estado'] === 200) {
        unset($_SESSION['csrf_retiro_rol_usuario'][$claveSesion]);
        $_SESSION['mensaje_rol_usuario'] = 'Rol retirado correctamente. '
            . 'Los permisos del usuario se comprueban nuevamente en la API.';
        header('Location: /roles-usuarios', true, 303);
        exit;
    }

    $estado = (int) $retiro['estado'];
    if ($estado === 409) {
        $errores = ['No se puede retirar el rol al último administrador activo.'];
    } elseif ($estado === 404) {
        $errores = ['La asignación ya no existe. Actualiza el listado.'];
    } elseif (in_array($estado, [401, 403], true)) {
        $errores = ['Tu sesión no tiene autorización vigente para retirar roles.'];
    } else {
        $estado = $retiro['disponible'] ? 502 : 503;
        $errores = ['No fue posible retirar el rol. Inténtalo nuevamente.'];
    }

    $consultaUsuario = obtenerUsuario($usuarioId);
    $registro = $consultaUsuario['datos']['datos'] ?? null;
    $username = $consultaUsuario['correcta'] && is_array($registro)
        ? (string) ($registro['username'] ?? '')
        : ('Usuario ID ' . $usuarioId);

    http_response_code($estado);
    mostrarConfirmacionRetiroRolUsuario($usuarioId, $rolId, $username, $errores);
    exit;
}
