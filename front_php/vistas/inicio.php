<?php

declare(strict_types=1);

require_once __DIR__ . '/compartidas/plantilla.php';

/** Panel de las entidades de V1 y las primeras de V2. */
function mostrarInicio(): void
{
    $recursos = [
        ['titulo' => 'Áreas de conocimiento', 'ruta' => '/areas-conocimiento',
         'descripcion' => 'Consulte y administre las grandes áreas, áreas y disciplinas.'],
        ['titulo' => 'Objetivos de Desarrollo Sostenible', 'ruta' => '/objetivos-desarrollo-sostenible',
         'descripcion' => 'Registre, edite y retire los objetivos y sus categorías.'],
        ['titulo' => 'Áreas de aplicación', 'ruta' => '/areas-aplicacion',
         'descripcion' => 'Gestione las áreas de aplicación del módulo.'],
        ['titulo' => 'Términos clave', 'ruta' => '/terminos-clave',
         'descripcion' => 'Administre términos clave y sus traducciones.'],
        ['titulo' => 'Universidades', 'ruta' => '/universidades',
         'descripcion' => 'Consulte y actualice la información de las universidades.'],
        ['titulo' => 'Líneas de investigación', 'ruta' => '/lineas-investigacion',
         'descripcion' => 'Gestione las líneas de investigación y sus descripciones.'],
        ['titulo' => 'Grupos de investigación', 'ruta' => '/grupos-investigacion',
         'descripcion' => 'Registre grupos y asócielos con universidades activas.'],
        ['titulo' => 'Semilleros', 'ruta' => '/semilleros',
         'descripcion' => 'Administre semilleros asociados con grupos de investigación activos.'],
        ['titulo' => 'Docentes', 'ruta' => '/docentes',
         'descripcion' => 'Gestione docentes, su perfil y su línea de investigación principal.'],
        ['titulo' => 'Participaciones en grupos', 'ruta' => '/participaciones-grupo',
         'descripcion' => 'Asocie docentes con grupos de investigación, sus roles y fechas de participación.'],
    ];

    ob_start();
    ?>
    <div class="py-3">
        <div class="p-4 mb-4 bg-white border rounded-3 shadow-sm panel-portada">
            <h1 class="display-6 fw-bold">Panel de investigación</h1>
            <p class="mb-0 text-muted">
                Seleccione un recurso para consultar o administrar sus registros.
            </p>
        </div>
        <div class="row g-3">
            <?php foreach ($recursos as $recurso): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm tarjeta-recurso">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5 card-title">
                                <?= htmlspecialchars($recurso['titulo'], ENT_QUOTES, 'UTF-8') ?>
                            </h2>
                            <p class="card-text text-muted flex-grow-1">
                                <?= htmlspecialchars($recurso['descripcion'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <a class="btn btn-primary align-self-start"
                               href="<?= htmlspecialchars($recurso['ruta'], ENT_QUOTES, 'UTF-8') ?>">
                                Gestionar
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    renderizarPlantilla('Inicio', (string) ob_get_clean());
}
