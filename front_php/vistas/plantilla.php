<?php

declare(strict_types=1);

/** Plantilla compartida por todas las pantallas, incluidas las vistas originales. */
function renderizarPlantilla(string $titulo, string $contenido): void
{
    $tituloSeguro = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    $navegacion = [
        ['/areas-conocimiento', 'Áreas de conocimiento'],
        ['/objetivos-desarrollo-sostenible', 'ODS'],
        ['/areas-aplicacion', 'Áreas de aplicación'],
        ['/terminos-clave', 'Términos clave'],
        ['/universidades', 'Universidades'],
        ['/lineas-investigacion', 'Líneas de investigación'],
    ];
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $tituloSeguro ?> | Investigación</title>
        <link rel="stylesheet" href="/publico/bootstrap.min.css">
    </head>
    <body class="bg-light">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid px-lg-4">
                <a class="navbar-brand" href="/">Investigación</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navegacionInvestigacion" aria-controls="navegacionInvestigacion"
                        aria-expanded="false" aria-label="Mostrar navegación">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navegacionInvestigacion">
                    <div class="navbar-nav ms-auto flex-wrap">
                        <a class="nav-link" href="/">Inicio</a>
                        <?php foreach ($navegacion as [$ruta, $nombre]): ?>
                            <a class="nav-link" href="<?= htmlspecialchars($ruta, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </nav>
        <main class="container py-4">
            <?= $contenido ?>
        </main>
        <footer class="border-top bg-white py-3 mt-5">
            <div class="container text-center text-muted">Módulo de Investigación</div>
        </footer>
        <script src="/publico/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
