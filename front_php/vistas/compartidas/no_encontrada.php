<?php

declare(strict_types=1);

require_once __DIR__ . '/plantilla.php';

/**
 * Muestra una pantalla de recurso o ruta no encontrada.
 */
function mostrarNoEncontrada(
    string $titulo = 'Contenido no encontrado',
    string $mensaje = 'El contenido solicitado no está disponible.'
): void {
    $tituloSeguro = htmlspecialchars(
        $titulo,
        ENT_QUOTES,
        'UTF-8'
    );

    $mensajeSeguro = htmlspecialchars(
        $mensaje,
        ENT_QUOTES,
        'UTF-8'
    );

    $contenido = <<<HTML
<div class="row justify-content-center">

    <div class="col-lg-7">

        <div class="alert alert-warning shadow-sm">

            <h1 class="h4">
                {$tituloSeguro}
            </h1>

            <p class="mb-3">
                {$mensajeSeguro}
            </p>

            <a
                href="/"
                class="btn btn-outline-dark"
            >
                Volver al inicio
            </a>

        </div>

    </div>

</div>
HTML;

    renderizarPlantilla(
        $titulo,
        $contenido
    );
}