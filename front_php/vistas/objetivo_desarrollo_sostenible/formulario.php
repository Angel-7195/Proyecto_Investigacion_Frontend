<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/crud_catalogos.php';

/** Punto de entrada propio del formulario de objetivo_desarrollo_sostenible. */
function mostrarFormularioObjetivosDesarrolloSostenible(
    string $modo,
    array $valores = [],
    array $errores = []
): void {
    mostrarFormularioCatalogoCompartido('objetivo_desarrollo_sostenible', $modo, $valores, $errores);
}
