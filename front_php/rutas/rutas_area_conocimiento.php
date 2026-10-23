<?php

declare(strict_types=1);

/** Rutas propias de area_conocimiento: listado, formulario y operaciones CRUD. */
require_once __DIR__ . '/compartidas/acciones_catalogo.php';
require_once __DIR__ . '/../vistas/area_conocimiento/lista.php';
require_once __DIR__ . '/../vistas/area_conocimiento/formulario.php';

if (atenderRutaCatalogo('area_conocimiento', $ruta, $metodo)) {
    exit;
}
