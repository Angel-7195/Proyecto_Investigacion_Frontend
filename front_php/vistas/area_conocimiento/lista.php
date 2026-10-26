<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/crud_catalogos.php';

/** Punto de entrada propio del listado de area_conocimiento. */
function mostrarListaAreaConocimiento(array $errores = []): void
{
    mostrarListaCatalogoCompartida('area_conocimiento', $errores);
}
