<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/crud_catalogos.php';

/** Punto de entrada propio del formulario de area_conocimiento. */
function mostrarFormularioAreaConocimiento(
    string $modo,
    array $valores = [],
    array $errores = []
): void {
    mostrarFormularioCatalogoCompartido('area_conocimiento', $modo, $valores, $errores);
}
