<?php

declare(strict_types=1);

/** Rutas HTML de docente. El acceso a MariaDB corresponde exclusivamente a la API. */
require_once __DIR__ . '/../vistas/docente/lista.php';
require_once __DIR__ . '/../vistas/docente/formulario.php';

/** La cédula es una clave primaria INT positivo, no autoincremental. */
function cedulaValidaDocente(mixed $valor): ?int
{
    if (!is_scalar($valor)) {
        return null;
    }
    $cedula = filter_var((string) $valor, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]);
    return $cedula === false ? null : $cedula;
}

/** Prepara todos los campos editables con el tipo JSON requerido por el backend. */
function datosDesdeFormularioDocente(array $formulario): array
{
    $campos = [
        'nombres', 'apellidos', 'genero', 'cargo', 'fecha_nacimiento', 'correo',
        'telefono', 'url_cvlac', 'fecha_actualizacion', 'escalafon', 'perfil',
        'conv_minciencia', 'nacionalidaad',
    ];
    $datos = [];
    foreach ($campos as $campo) {
        $datos[$campo] = trim((string) ($formulario[$campo] ?? ''));
    }
    $categoria = trim((string) ($formulario['cat_minciencia'] ?? ''));
    $datos['cat_minciencia'] = $categoria === '' ? null : $categoria;

    $linea = trim((string) ($formulario['linea_investigacion_principal'] ?? ''));
    $idLinea = $linea === '' ? null : cedulaValidaDocente($linea);
    $datos['linea_investigacion_principal'] = $linea === '' ? null : ($idLinea ?? $linea);
    return $datos;
}

/** Se muestran exclusivamente líneas activas, consultadas mediante HTTP. */
function opcionesLineasDocente(): array
{
    $respuesta = listarLineasInvestigacion();
    if (!$respuesta['correcta']) {
        return [[], false];
    }
    $lineas = $respuesta['datos']['datos'] ?? [];
    if (!is_array($lineas)) {
        return [[], false];
    }
    return [$lineas, true];
}

function formularioConLineasDocente(
    string $modo,
    array $valores = [],
    array $errores = []
): void {
    [$lineas, $disponible] = opcionesLineasDocente();
    mostrarFormularioDocente($modo, $valores, $errores, $lineas, $disponible);
}

function erroresRespuestaDocente(array $respuesta): array
{
    if (!empty($respuesta['error'])) {
        return [(string) $respuesta['error']];
    }
    $datos = $respuesta['datos'] ?? [];
    if (isset($datos['errores']) && is_array($datos['errores']) && $datos['errores'] !== []) {
        return array_map('strval', $datos['errores']);
    }
    $mensaje = $datos['detalle'] ?? $datos['mensaje'] ?? null;
    return [is_string($mensaje) && $mensaje !== '' ? $mensaje : 'No fue posible completar la operación.'];
}

function redirigirListadoDocente(string $mensaje): never
{
    $_SESSION['mensaje_docente'] = $mensaje;
    header('Location: /docentes', true, 303);
    exit;
}

// LISTAR
if ($ruta === '/docentes' && $metodo === 'GET') {
    mostrarListaDocentes();
    exit;
}

// FORMULARIO DE CREACIÓN
if ($ruta === '/docentes/nuevo' && $metodo === 'GET') {
    formularioConLineasDocente('crear');
    exit;
}

// FORMULARIO DE EDICIÓN
if ($ruta === '/docentes/editar' && $metodo === 'GET') {
    $cedula = cedulaValidaDocente($_GET['cedula'] ?? null);
    if ($cedula === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique una cédula válida.');
        exit;
    }
    $respuesta = obtenerDocente($cedula);
    if (!$respuesta['correcta']) {
        http_response_code($respuesta['estado'] === 404 ? 404 : 503);
        mostrarNoEncontrada(
            $respuesta['estado'] === 404 ? 'Docente no encontrado' : 'Servicio no disponible',
            $respuesta['estado'] === 404
                ? 'El docente solicitado no existe o está retirado.'
                : 'No fue posible consultar al docente en la API.'
        );
        exit;
    }
    formularioConLineasDocente('editar', $respuesta['datos']);
    exit;
}

// CREACIÓN
if ($ruta === '/docentes/crear' && $metodo === 'POST') {
    $valores = $_POST;
    $cedula = cedulaValidaDocente($_POST['cedula'] ?? null);
    if ($cedula === null) {
        http_response_code(422);
        formularioConLineasDocente('crear', $valores, ['La cédula debe ser un entero positivo válido.']);
        exit;
    }
    $linea = (string) ($_POST['linea_investigacion_principal'] ?? '');
    if ($linea !== '' && cedulaValidaDocente($linea) === null) {
        http_response_code(422);
        formularioConLineasDocente('crear', $valores, ['Seleccione una línea de investigación válida o deje el campo vacío.']);
        exit;
    }
    $datos = datosDesdeFormularioDocente($_POST);
    $datos['cedula'] = $cedula;
    $respuesta = crearDocente($datos);
    if ($respuesta['correcta']) {
        redirigirListadoDocente('Docente registrado correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [400, 409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConLineasDocente('crear', $valores, erroresRespuestaDocente($respuesta));
    exit;
}

// EDICIÓN: PUT completo / PATCH sólo de los campos modificados
if ($ruta === '/docentes/actualizar' && $metodo === 'POST') {
    $cedula = cedulaValidaDocente($_POST['cedula'] ?? null);
    if ($cedula === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique una cédula válida para actualizar.');
        exit;
    }
    $valores = $_POST;
    $linea = (string) ($_POST['linea_investigacion_principal'] ?? '');
    if ($linea !== '' && cedulaValidaDocente($linea) === null) {
        http_response_code(422);
        formularioConLineasDocente('editar', $valores, ['Seleccione una línea válida o deje el campo vacío.']);
        exit;
    }
    $datos = datosDesdeFormularioDocente($_POST);
    $accion = (string) ($_POST['accion'] ?? '');
    if ($accion === 'completo') {
        $respuesta = reemplazarDocente($cedula, $datos);
    } elseif ($accion === 'parcial') {
        $respuestaActual = obtenerDocente($cedula);
        if (!$respuestaActual['correcta']) {
            http_response_code($respuestaActual['estado'] === 404 ? 404 : 503);
            formularioConLineasDocente('editar', $valores, erroresRespuestaDocente($respuestaActual));
            exit;
        }
        $actual = $respuestaActual['datos'];
        $diferencias = [];
        foreach ($datos as $campo => $valor) {
            $anterior = $actual[$campo] ?? null;
            if ($campo === 'linea_investigacion_principal' && $anterior !== null) {
                $anterior = (int) $anterior;
            }
            if ($valor !== $anterior) {
                $diferencias[$campo] = $valor;
            }
        }
        if ($diferencias === []) {
            formularioConLineasDocente('editar', $valores, ['No realizó ningún cambio.']);
            exit;
        }
        $respuesta = actualizarDocente($cedula, $diferencias);
    } else {
        http_response_code(400);
        formularioConLineasDocente('editar', $valores, ['No se reconoció la operación solicitada.']);
        exit;
    }
    if ($respuesta['correcta']) {
        redirigirListadoDocente('Docente actualizado correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [400, 404, 409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConLineasDocente('editar', $valores, erroresRespuestaDocente($respuesta));
    exit;
}

// RETIRO LÓGICO
if ($ruta === '/docentes/retirar' && $metodo === 'POST') {
    $cedula = cedulaValidaDocente($_POST['cedula'] ?? null);
    if ($cedula === null) {
        http_response_code(400);
        mostrarListaDocentes(['La cédula recibida no es válida.']);
        exit;
    }
    $respuesta = retirarDocente($cedula);
    if ($respuesta['correcta']) {
        redirigirListadoDocente('Docente retirado correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [404, 409], true)
        ? $respuesta['estado'] : 503);
    mostrarListaDocentes(erroresRespuestaDocente($respuesta));
    exit;
}
