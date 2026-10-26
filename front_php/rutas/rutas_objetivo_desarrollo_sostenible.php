<?php

declare(strict_types=1);

/** Rutas propias de objetivo_desarrollo_sostenible: listado, formulario y operaciones CRUD. */
require_once __DIR__ . '/compartidas/acciones_catalogo.php';
require_once __DIR__ . '/../vistas/objetivo_desarrollo_sostenible/lista.php';
require_once __DIR__ . '/../vistas/objetivo_desarrollo_sostenible/formulario.php';

if (atenderRutaCatalogo('objetivo_desarrollo_sostenible', $ruta, $metodo)) {
    exit;
}
