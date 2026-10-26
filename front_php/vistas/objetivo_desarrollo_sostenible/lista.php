<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/crud_catalogos.php';

/** Punto de entrada propio del listado de objetivo_desarrollo_sostenible. */
function mostrarListaObjetivosDesarrolloSostenible(array $errores = []): void
{
    mostrarListaCatalogoCompartida('objetivo_desarrollo_sostenible', $errores);
}
