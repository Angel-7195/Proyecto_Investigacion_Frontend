<?php

declare(strict_types=1);

/** Rutas propias de area_aplicacion: listado, formulario y operaciones CRUD. */
require_once __DIR__ . '/compartidas/acciones_catalogo.php';
require_once __DIR__ . '/../vistas/area_aplicacion/lista.php';
require_once __DIR__ . '/../vistas/area_aplicacion/formulario.php';

if (atenderRutaCatalogo('area_aplicacion', $ruta, $metodo)) {
    exit;
}
