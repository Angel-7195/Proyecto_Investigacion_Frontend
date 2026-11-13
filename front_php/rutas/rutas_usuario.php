<?php

declare(strict_types=1);

/** Rutas administrativas de usuario — V3. Cargadas desde index.php. */
require_once __DIR__ . '/../vistas/usuario/lista.php';
require_once __DIR__ . '/../vistas/usuario/formulario.php';

// Únicamente procesar las rutas de este módulo.
if (!in_array($ruta, ['/usuarios', '/usuarios/nuevo', '/usuarios/crear', '/usuarios/editar', '/usuarios/actualizar'], true)) {
    return;
}

// La sesión de PHP controla la navegación; la API vuelve a validar el rol real.
$rolesUsuario = $_SESSION['usuario']['roles'] ?? [];
$esAdminUsuario = is_array($rolesUsuario)
    && in_array('admin', $rolesUsuario, true);

if (!$esAdminUsuario) {
    http_response_code(403);
    renderizarPlantilla(
        'Acceso denegado',
        '<div class="alert alert-warning" role="alert">'
        . '<h1 class="h4">Acceso denegado</h1>'
        . '<p>La gestión de usuarios es exclusiva del administrador.</p>'
        . '<a class="btn btn-outline-dark" href="/">Volver al inicio</a>'
        . '</div>'
    );
    exit;
}

header('Cache-Control: no-store, private');

// ----------------------------------------------------------------------
// LISTAR USUARIOS
// ----------------------------------------------------------------------
if ($ruta === '/usuarios') {
    if ($metodo !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        exit;
    }

    mostrarListaUsuarios();
    exit;
}

// ----------------------------------------------------------------------
// FORMULARIO DE REGISTRO
// ----------------------------------------------------------------------
if ($ruta === '/usuarios/nuevo') {
    if ($metodo !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        exit;
    }

    mostrarFormularioUsuario();
    exit;
}

// ----------------------------------------------------------------------
// FORMULARIO DE EDICIÓN — PERFIL DE USUARIO
// ----------------------------------------------------------------------
if ($ruta === '/usuarios/editar') {
    if ($metodo !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        exit;
    }

    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if (!is_int($id)) {
        http_response_code(400);
        renderizarPlantilla('Solicitud inválida',
            '<div class="alert alert-warning">El ID de usuario no es válido.</div>');
        exit;
    }

    $respuesta = obtenerUsuario($id);
    $datos = $respuesta['datos']['datos'] ?? null;

    if ($respuesta['correcta'] && is_array($datos)) {
        if (($_GET['seccion'] ?? null) === 'eliminar') {
            mostrarConfirmacionDesactivarUsuario($datos);
            exit;
        }

        // La contraseña se edita en un formulario independiente del perfil.
        if (($_GET['seccion'] ?? null) === 'password') {
            mostrarFormularioPasswordUsuario($datos);
            exit;
        }

        // Guardamos el estado original en el servidor para detectar cambios en PATCH.
        // Nunca confiamos en campos ocultos del navegador para esta comparación.
        $_SESSION['usuario_edicion_original'][$id] = [
            'username' => (string) ($datos['username'] ?? ''),
            'email' => (string) ($datos['email'] ?? ''),
            'nombre_completo' => (string) ($datos['nombre_completo'] ?? ''),
        ];
        mostrarFormularioEditarUsuario($datos);
        exit;
    }

    $estado = (int) $respuesta['estado'];
    http_response_code(in_array($estado, [401, 403, 404], true)
        ? $estado : ($respuesta['disponible'] ? 502 : 503));
    $mensaje = $estado === 404
        ? 'El usuario solicitado no existe o está inactivo.'
        : 'No fue posible consultar los datos del usuario.';
    renderizarPlantilla('No fue posible editar usuario',
        '<div class="alert alert-warning" role="alert">'
        . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</div>'
        . '<a href="/usuarios" class="btn btn-outline-secondary">Volver al listado</a>');
    exit;
}

// ----------------------------------------------------------------------
// GUARDAR PERFIL, CAMBIAR CONTRASEÑA O DESACTIVAR — POST EN EL FRONTEND
// ----------------------------------------------------------------------
if ($ruta === '/usuarios/actualizar') {
    if ($metodo !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit;
    }

    $accion = $_POST['accion'] ?? 'completo';
    $csrfEnviado = $_POST['csrf_token'] ?? null;
    $csrfGuardado = $accion === 'eliminar'
        ? ($_SESSION['csrf_usuario_eliminar'] ?? null)
        : ($_SESSION['csrf_usuario_editar'] ?? null);
    if (
        !is_string($csrfEnviado) || !is_string($csrfGuardado)
        || !hash_equals($csrfGuardado, $csrfEnviado)
    ) {
        http_response_code(403);
        exit('Solicitud no autorizada.');
    }

    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    if (!is_int($id)) {
        http_response_code(400);
        exit('El ID de usuario no es válido.');
    }

    // La desactivación es lógica: se delega al DELETE protegido del backend.
    // Antes de ejecutarlo, se vuelve a consultar el registro para comprobar
    // que el identificador aún corresponde al usuario confirmado por ADMIN.
    if ($accion === 'eliminar') {
        $usernameEsperado = $_POST['username_confirmado'] ?? null;
        if (!is_string($usernameEsperado) || $usernameEsperado === '') {
            http_response_code(400);
            renderizarPlantilla('Solicitud inválida',
                '<div class="alert alert-warning">Falta confirmar la identidad del usuario.</div>');
            exit;
        }

        $consulta = obtenerUsuario($id);
        $registro = $consulta['datos']['datos'] ?? null;

        if (!$consulta['correcta'] || !is_array($registro)) {
            $estadoConsulta = (int) $consulta['estado'];
            http_response_code(in_array($estadoConsulta, [401, 403, 404], true)
                ? $estadoConsulta : ($consulta['disponible'] ? 502 : 503));
            renderizarPlantilla('No fue posible desactivar usuario',
                '<div class="alert alert-warning">'
                . 'El usuario ya no está disponible o no se pudo verificar su identidad.</div>'
                . '<a class="btn btn-outline-secondary" href="/usuarios">Volver al listado</a>');
            exit;
        }

        if (!hash_equals((string) ($registro['username'] ?? ''), $usernameEsperado)) {
            http_response_code(409);
            mostrarConfirmacionDesactivarUsuario($registro, [
                'El nombre de usuario cambió desde que abriste la confirmación. Revisa el registro nuevamente.'
            ]);
            exit;
        }

        $retiro = adaptarRespuestaApi(llamarApi('DELETE', 'usuario', (string) $id));
        if ($retiro['correcta'] && (int) $retiro['estado'] === 200) {
            unset($_SESSION['csrf_usuario_eliminar']);
            unset($_SESSION['usuario_edicion_original'][$id]);
            $_SESSION['mensaje_usuarios'] = 'Usuario "' . (string) $registro['username']
                . '" desactivado correctamente. El registro permanece en la base de datos.';
            header('Location: /usuarios', true, 303);
            exit;
        }

        $estadoRetiro = (int) $retiro['estado'];
        if ($estadoRetiro === 409) {
            $erroresRetiro = ['No es posible desactivar al último administrador activo.'];
        } elseif ($estadoRetiro === 404) {
            $erroresRetiro = ['El usuario ya no está activo o no existe.'];
        } elseif (in_array($estadoRetiro, [401, 403], true)) {
            $erroresRetiro = ['Tu sesión ya no tiene permisos vigentes. Inicia sesión nuevamente.'];
        } else {
            $erroresRetiro = ['No fue posible desactivar el usuario. Intenta nuevamente.'];
        }

        http_response_code(in_array($estadoRetiro, [401, 403, 404, 409], true)
            ? $estadoRetiro : ($retiro['disponible'] ? 502 : 503));
        mostrarConfirmacionDesactivarUsuario($registro, $erroresRetiro);
        exit;
    }

    // El formulario de contraseña usa esta ruta, pero no modifica el perfil.
    if ($accion === 'password') {
        $password = $_POST['password'] ?? null;
        $confirmacion = $_POST['confirmar_password'] ?? null;
        $erroresPassword = [];
        $codigoHttp = 422;

        // No se aplica trim(): los espacios pueden formar parte de la clave.
        if (!is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
            $erroresPassword[] = 'La nueva contraseña debe tener entre 12 y 72 bytes.';
        }
        if (!is_string($confirmacion) || !is_string($password)
            || !hash_equals($password, $confirmacion)) {
            $erroresPassword[] = 'Las contraseñas no coinciden.';
        }

        if ($erroresPassword === []) {
            // Solamente la contraseña llega a la API, sin confirmación ni datos de perfil.
            $resultado = adaptarRespuestaApi(
                llamarApi('PATCH', 'usuario', [(string) $id, 'password'], ['password' => $password])
            );

            if ($resultado['correcta'] && (int) $resultado['estado'] === 200) {
                unset($_SESSION['csrf_usuario_editar']);
                unset($_SESSION['usuario_edicion_original'][$id]);
                $_SESSION['mensaje_usuarios'] = 'Contraseña actualizada correctamente. '
                    . 'Si cambiaste la tuya, cierra sesión y vuelve a iniciar sesión.';
                header('Location: /usuarios', true, 303);
                exit;
            }

            $codigoHttp = (int) $resultado['estado'];
            if ($codigoHttp === 422) {
                $detalles = $resultado['datos']['errores'] ?? [];
                $erroresPassword = is_array($detalles) && $detalles !== []
                    ? array_map('strval', $detalles)
                    : [(string) ($resultado['datos']['mensaje'] ?? 'La contraseña no es válida.')];
            } elseif ($codigoHttp === 404) {
                $erroresPassword = ['El usuario no existe o está desactivado.'];
            } elseif (in_array($codigoHttp, [401, 403], true)) {
                $erroresPassword = ['Tu sesión ya no tiene autorización. Inicia sesión nuevamente.'];
            } else {
                $codigoHttp = $resultado['disponible'] ? 502 : 503;
                $erroresPassword = ['No fue posible cambiar la contraseña. Inténtalo de nuevo.'];
            }
        }

        // Ante un error se recupera únicamente el perfil para volver a dibujar
        // el formulario; jamás se guardan ni se reimprimen las contraseñas.
        $consulta = obtenerUsuario($id);
        $datosUsuario = $consulta['datos']['datos'] ?? null;
        if ($consulta['correcta'] && is_array($datosUsuario)) {
            http_response_code($codigoHttp);
            mostrarFormularioPasswordUsuario($datosUsuario, $erroresPassword);
        } else {
            $estadoConsulta = (int) $consulta['estado'];
            http_response_code(in_array($estadoConsulta, [401, 403, 404], true)
                ? $estadoConsulta : ($consulta['disponible'] ? 502 : 503));
            renderizarPlantilla('Error al cambiar contraseña',
                '<div class="alert alert-warning" role="alert">'
                . 'No se pudo volver a consultar el usuario.</div>');
        }
        exit;
    }

    $username = $_POST['username'] ?? null;
    $email = $_POST['email'] ?? null;
    $nombre = $_POST['nombre_completo'] ?? null;

    $valores = [
        'id' => $id,
        'username' => is_string($username) ? trim($username) : '',
        'email' => is_string($email) ? trim($email) : '',
        'nombre_completo' => is_string($nombre) ? trim($nombre) : '',
    ];

    $errores = [];
    if ($valores['username'] === '' || preg_match_all('/./us', $valores['username']) > 100) {
        $errores[] = 'El usuario es obligatorio y no debe superar 100 caracteres.';
    }
    if (filter_var($valores['email'], FILTER_VALIDATE_EMAIL) === false
        || strlen($valores['email']) > 150) {
        $errores[] = 'El correo electrónico debe ser válido (máximo 150 caracteres).';
    }
    if (preg_match_all('/./us', $valores['nombre_completo']) > 200) {
        $errores[] = 'El nombre completo no debe superar 200 caracteres.';
    }

    if ($errores !== []) {
        http_response_code(422);
        mostrarFormularioEditarUsuario($valores, $errores);
        exit;
    }

    // El formulario HTML siempre envía POST; la API recibirá PUT o PATCH.
    if (!is_string($accion) || !in_array($accion, ['completo', 'parcial'], true)) {
        http_response_code(422);
        mostrarFormularioEditarUsuario($valores, ['Operación de edición no válida.']);
        exit;
    }

    // El perfil no modifica password, roles, estado activo ni ID.
    if ($accion === 'parcial') {
        $original = $_SESSION['usuario_edicion_original'][$id] ?? null;
        if (!is_array($original)
            || !isset($original['username'], $original['email'], $original['nombre_completo'])
        ) {
            http_response_code(409);
            mostrarFormularioEditarUsuario($valores, [
                'No se encontró la versión original. Vuelve al listado y abre la edición nuevamente.'
            ]);
            exit;
        }

        // Solo se envían los campos que realmente cambiaron.
        $cambios = [];
        foreach (['username', 'email', 'nombre_completo'] as $campo) {
            if ($valores[$campo] !== $original[$campo]) {
                $cambios[$campo] = $valores[$campo];
            }
        }

        if ($cambios === []) {
            http_response_code(422);
            mostrarFormularioEditarUsuario($valores, ['No realizaste ningún cambio.']);
            exit;
        }

        $respuesta = actualizarUsuarioParcial($id, $cambios);
    } else {
        $respuesta = reemplazarUsuario($id, [
            'username' => $valores['username'],
            'email' => $valores['email'],
            'nombre_completo' => $valores['nombre_completo'],
        ]);
    }

    if ($respuesta['correcta'] && $respuesta['estado'] === 200) {
        unset($_SESSION['csrf_usuario_editar']);
        unset($_SESSION['usuario_edicion_original'][$id]);
        $_SESSION['mensaje_usuarios'] = $accion === 'parcial'
            ? 'Se actualizaron únicamente los campos modificados (PATCH).'
            : 'Perfil de usuario actualizado correctamente (PUT).';
        header('Location: /usuarios', true, 303);
        exit;
    }

    $estado = (int) $respuesta['estado'];
    $mensaje = $respuesta['datos']['mensaje'] ?? '';
    if ($estado === 409) {
        $errores = ['El nombre de usuario o el correo ya están registrados.'];
    } elseif ($estado === 422) {
        $detalles = $respuesta['datos']['errores'] ?? [];
        $errores = is_array($detalles) && $detalles !== []
            ? array_map('strval', $detalles) : [(string) ($mensaje ?: 'Datos inválidos.')];
    } elseif ($estado === 404) {
        $errores = ['El usuario ya no existe o fue desactivado.'];
    } elseif (in_array($estado, [401, 403], true)) {
        $errores = ['No tienes autorización vigente. Vuelve a iniciar sesión.'];
    } else {
        $errores = ['No fue posible actualizar el perfil del usuario.'];
    }
    http_response_code(in_array($estado, [401, 403, 404, 409, 422], true)
        ? $estado : ($respuesta['disponible'] ? 502 : 503));
    mostrarFormularioEditarUsuario($valores, $errores);
    exit;
}

// ----------------------------------------------------------------------
// REGISTRAR USUARIO
// ----------------------------------------------------------------------
if ($ruta === '/usuarios/crear') {
    if ($metodo !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit;
    }

    $csrfEnviado = $_POST['csrf_token'] ?? null;
    $csrfGuardado = $_SESSION['csrf_usuario'] ?? null;
    if (
        !is_string($csrfEnviado)
        || !is_string($csrfGuardado)
        || !hash_equals($csrfGuardado, $csrfEnviado)
    ) {
        http_response_code(403);
        exit('Solicitud no autorizada.');
    }

    $username = $_POST['username'] ?? null;
    $email = $_POST['email'] ?? null;
    $nombre = $_POST['nombre_completo'] ?? null;
    $password = $_POST['password'] ?? null;
    $confirmacion = $_POST['confirmar_password'] ?? null;

    $valores = [
        'username' => is_string($username) ? trim($username) : '',
        'email' => is_string($email) ? trim($email) : '',
        'nombre_completo' => is_string($nombre) ? trim($nombre) : '',
    ];

    $errores = [];
    if ($valores['username'] === '') {
        $errores[] = 'El nombre de usuario es obligatorio.';
    }
    if (filter_var($valores['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errores[] = 'El correo electrónico debe ser válido.';
    }
    if (!is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
        $errores[] = 'La contraseña debe tener entre 12 y 72 bytes.';
    }
    if (!is_string($confirmacion) || $confirmacion !== $password) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if ($errores !== []) {
        http_response_code(422);
        mostrarFormularioUsuario($valores, $errores);
        exit;
    }

    // No incluir la confirmación en el JSON y nunca persistir contraseñas en sesión.
    $respuesta = crearUsuario([
        'username' => $valores['username'],
        'email' => $valores['email'],
        'nombre_completo' => $valores['nombre_completo'],
        'password' => $password,
    ]);

    if ($respuesta['correcta'] && $respuesta['estado'] === 201) {
        unset($_SESSION['csrf_usuario']);
        $_SESSION['mensaje_usuarios'] = 'Usuario registrado correctamente. Asigna un rol desde Gestión de roles por usuario.';
        header('Location: /usuarios', true, 303);
        exit;
    }

    $estado = (int) $respuesta['estado'];
    if ($estado === 409 || $estado === 422) {
        http_response_code($estado);
        $detalles = $respuesta['datos']['errores'] ?? [];
        if (!is_array($detalles)) {
            $detalles = [];
        }
        $errores = array_values(array_map('strval', $detalles));
        if ($errores === []) {
            $errores[] = (string) ($respuesta['datos']['mensaje'] ?? 'Revisa los datos del usuario.');
        }
    } elseif (!$respuesta['disponible']) {
        http_response_code(503);
        $errores = ['No fue posible conectar con la API.'];
    } elseif ($estado === 401 || $estado === 403) {
        http_response_code($estado);
        $errores = ['Tu sesión no tiene autorización vigente. Vuelve a iniciar sesión.'];
    } else {
        http_response_code(502);
        $errores = ['No fue posible registrar el usuario.'];
    }

    mostrarFormularioUsuario($valores, $errores);
    exit;
}
