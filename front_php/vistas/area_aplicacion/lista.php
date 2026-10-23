<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/crud_catalogos.php';

/** Punto de entrada propio del listado de area_aplicacion. */
function mostrarListaAreaAplicacion(array $errores = []): void
{
    mostrarListaCatalogoCompartida('area_aplicacion', $errores);
}
