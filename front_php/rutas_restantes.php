<?php

declare(strict_types=1);

require_once __DIR__ . '/vistas/crud_restantes.php';

/** ID aportado en formulario o query string: nunca se toma de una ruta arbitraria. */
function validarIdCrudRestante(string $recurso, mixed $id): ?string
{
    if (!is_string($id) && !is_int($id)) {
        return null;
    }
    $id = trim((string) $id);
    if ($id === '') {
        return null;
    }
    if ($recurso === 'area_conocimiento') {
        return mbLongitudCrudRestante($id) <= 6 ? $id : null;
    }
    return ctype_digit($id)
        && filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) !== false
        ? $id
        : null;
}

function mbLongitudCrudRestante(string $valor): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($valor, 'UTF-8');
    }
    $longitud = preg_match_all('/./us', $valor);
    return $longitud === false ? strlen($valor) : $longitud;
}

/** Reglas simples del formulario. La API conserva la validación definitiva. */
function validarCamposCrudRestante(
    string $recurso,
    array $valores,
    bool $incluirId,
    bool $esParcial = false
): array {
    $config = configuracionCrudRestantes()[$recurso];
    $errores = [];
    foreach ($config['campos'] as $nombre => $campo) {
        if (!$incluirId && ($campo['clave'] ?? false)) {
            continue;
        }
        if ($esParcial && !array_key_exists($nombre, $valores)) {
            continue;
        }
        $valor = $valores[$nombre] ?? '';
        if (!is_string($valor) && !is_int($valor)) {
            $errores[] = 'El campo ' . $campo['etiqueta'] . ' no es válido.';
            continue;
        }
        $valor = trim((string) $valor);
        if ($valor === '') {
            $errores[] = 'El campo ' . $campo['etiqueta'] . ' es obligatorio.';
            continue;
        }
        if (($campo['clave'] ?? false) && validarIdCrudRestante($recurso, $valor) === null) {
            $errores[] = 'El identificador no tiene un formato válido.';
        } elseif (isset($campo['max']) && mbLongitudCrudRestante($valor) > $campo['max']) {
            $errores[] = 'El campo ' . $campo['etiqueta'] . ' supera los ' . $campo['max'] . ' caracteres.';
        }
    }
    return $errores;
}

/**
 * Maneja exclusivamente las tres rutas que faltaban; devuelve false
 * para que index.php continúe con los CRUD originales.
 */
function atenderRutasCrudRestantes(string $ruta, string $metodo): bool
{
    foreach (configuracionCrudRestantes() as $recurso => $config) {
        $base = $config['ruta'];
        if ($ruta !== $base && !str_starts_with($ruta, $base . '/')) {
            continue;
        }
        $accion = $ruta === $base ? '' : substr($ruta, strlen($base) + 1);

        if ($accion === '' && $metodo === 'GET') {
            mostrarListaCrudRestante($recurso);
            return true;
        }
        if ($accion === 'nuevo' && $metodo === 'GET') {
            mostrarFormularioCrudRestante($recurso, 'crear');
            return true;
        }
        if ($accion === 'editar' && $metodo === 'GET') {
            $id = validarIdCrudRestante($recurso, $_GET['id'] ?? null);
            if ($id === null) {
                http_response_code(400);
                mostrarErrorCrudRestante('Identificador no válido', ['Seleccione un registro de la lista.']);
                return true;
            }
            $respuesta = llamarApi('GET', $recurso, $id);
            if ((int) ($respuesta['codigo'] ?? 0) !== 200) {
                http_response_code((int) ($respuesta['codigo'] ?? 0) === 404 ? 404 : 503);
                mostrarErrorCrudRestante('No se puede editar este registro', erroresApiCrudRestante($respuesta));
                return true;
            }
            mostrarFormularioCrudRestante($recurso, 'editar', $respuesta['datos']);
            return true;
        }
        if ($accion === 'crear' && $metodo === 'POST') {
            $valores = is_array($_POST) ? $_POST : [];
            $errores = validarCamposCrudRestante($recurso, $valores, true);
            if ($errores !== []) {
                mostrarFormularioCrudRestante($recurso, 'crear', $valores, $errores);
                return true;
            }
            $datos = [];
            foreach ($config['campos'] as $campo => $opciones) {
                $valor = trim((string) $valores[$campo]);
                $datos[$campo] = $campo === 'id' && $opciones['tipo'] === 'number'
                    ? (int) $valor
                    : $valor;
            }
            $respuesta = llamarApi('POST', $recurso, '', $datos);
            if ((int) ($respuesta['codigo'] ?? 0) === 201) {
                $_SESSION['mensaje_crud_restante'] = 'Registro creado correctamente.';
                header('Location: ' . $base, true, 303);
                return true;
            }
            mostrarFormularioCrudRestante($recurso, 'crear', $valores, erroresApiCrudRestante($respuesta));
            return true;
        }
        if ($accion === 'actualizar' && $metodo === 'POST') {
            $valores = is_array($_POST) ? $_POST : [];
            $id = validarIdCrudRestante($recurso, $valores['id'] ?? null);
            if ($id === null) {
                http_response_code(400);
                mostrarErrorCrudRestante('Identificador no válido', ['Regrese al listado y seleccione un registro.']);
                return true;
            }
            $modo = ($valores['accion'] ?? 'completo') === 'parcial' ? 'PATCH' : 'PUT';
            $originales = is_array($valores['original'] ?? null) ? $valores['original'] : [];
            $datos = [];
            foreach ($config['campos'] as $campo => $opciones) {
                if ($opciones['clave'] ?? false) {
                    continue;
                }
                $valor = trim((string) ($valores[$campo] ?? ''));
                if ($modo === 'PUT' || !array_key_exists($campo, $originales)
                    || $valor !== (string) $originales[$campo]) {
                    $datos[$campo] = $valor;
                }
            }
            $errores = validarCamposCrudRestante($recurso, $datos, false, $modo === 'PATCH');
            if ($modo === 'PATCH' && $datos === []) {
                $errores[] = 'No hay cambios para guardar.';
            }
            if ($errores !== []) {
                mostrarFormularioCrudRestante($recurso, 'editar', $valores, $errores);
                return true;
            }
            $respuesta = llamarApi($modo, $recurso, $id, $datos);
            if ((int) ($respuesta['codigo'] ?? 0) === 200) {
                $_SESSION['mensaje_crud_restante'] = $modo === 'PUT'
                    ? 'Ficha completa guardada correctamente.'
                    : 'Cambios guardados correctamente.';
                header('Location: ' . $base, true, 303);
                return true;
            }
            mostrarFormularioCrudRestante($recurso, 'editar', $valores, erroresApiCrudRestante($respuesta));
            return true;
        }
        if ($accion === 'retirar' && $metodo === 'POST') {
            $id = validarIdCrudRestante($recurso, $_POST['id'] ?? null);
            if ($id === null) {
                mostrarListaCrudRestante($recurso, ['El identificador no es válido.']);
                return true;
            }
            $respuesta = llamarApi('DELETE', $recurso, $id);
            if ((int) ($respuesta['codigo'] ?? 0) === 200) {
                $_SESSION['mensaje_crud_restante'] = 'Registro retirado correctamente.';
                header('Location: ' . $base, true, 303);
                return true;
            }
            mostrarListaCrudRestante($recurso, erroresApiCrudRestante($respuesta));
            return true;
        }
        if (in_array($accion, ['', 'nuevo', 'editar', 'crear', 'actualizar', 'retirar'], true)) {
            http_response_code(405);
            mostrarErrorCrudRestante('Método no permitido', ['La ruta no admite este método HTTP.']);
            return true;
        }
        return false;
    }
    return false;
}
