<?php

declare(strict_types=1);

require_once __DIR__ . '/plantilla.php';

/**
 * Muestra la página principal del módulo de Investigación.
 */
function mostrarInicio(): void
{
    $contenido = <<<'HTML'
<div class="py-4">

    <div class="p-5 mb-4 bg-white border rounded-3 shadow-sm">
        <div class="container-fluid py-3">

            <h1 class="display-6 fw-bold">
                Módulo de Investigación
            </h1>

            <p class="col-md-8 fs-5 text-muted">
                Gestión de la información relacionada con el
                módulo de investigación.
            </p>

        </div>
    </div>

    <div class="row">

        <div class="col-md-6 col-lg-4 mb-4">

            <div class="card h-100 shadow-sm">

                <div class="card-body">

                    <h2 class="h5 card-title">
                        Términos clave
                    </h2>

                    <p class="card-text text-muted">
                        Consulte, registre, edite y retire
                        términos clave y sus traducciones.
                    </p>

                    <a
                        href="/terminos-clave"
                        class="btn btn-primary"
                    >
                        Gestionar términos clave
                    </a>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-lg-4 mb-4">

            <div class="card h-100 shadow-sm">

                <div class="card-body">

                    <h2 class="h5 card-title">
                        Universidades
                    </h2>

                    <p class="card-text text-muted">
                        Consulte, registre, edite y retire
                        universidades del módulo.
                    </p>

                    <a
                        href="/universidades"
                        class="btn btn-primary"
                    >
                        Gestionar universidades
                    </a>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-lg-4 mb-4">

            <div class="card h-100 shadow-sm">

                <div class="card-body">

                    <h2 class="h5 card-title">
                        Líneas de investigación
                    </h2>

                    <p class="card-text text-muted">
                        Consulte, registre, edite y retire
                        las líneas de investigación del módulo.
                    </p>

                    <a
                        href="/lineas-investigacion"
                        class="btn btn-primary"
                    >
                        Gestionar líneas de investigación
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>
HTML;

    renderizarPlantilla(
        'Inicio',
        $contenido
    );
}