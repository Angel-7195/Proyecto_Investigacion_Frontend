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
        ['/grupos-investigacion', 'Grupos de investigación'],
        ['/semilleros', 'Semilleros'],
        ['/docentes', 'Docentes'],
        ['/participaciones-grupo', 'Participaciones en grupos'],
        ['/participaciones-semillero', 'Participaciones en semilleros'],
        ['/asociaciones-ac-linea', 'Áreas por línea'],
        ['/asociaciones-ods-linea', 'ODS por línea'],
        ['/asociaciones-aa-linea', 'Aplicaciones por línea'],
        ['/asociaciones-grupo-linea', 'Grupos por línea'],
        ['/asociaciones-semillero-linea', 'Semilleros por línea'],
    ];
    // Verificar si existe una sesión autenticada.
    $sesionIniciada = !empty($_SESSION['jwt']);

    // Obtener los roles del usuario autenticado.
    $roles = $_SESSION['usuario']['roles'] ?? [];

    if (!is_array($roles)) {
        $roles = [];
    }

    // Comprobar los permisos del usuario.
    $esAdmin = $sesionIniciada && in_array('admin', $roles, true);
    $esConsulta = $sesionIniciada && in_array('consulta', $roles, true);

    // Crear token CSRF para el cierre de sesión.
    if ($sesionIniciada) {
        $_SESSION['csrf_logout'] ??= bin2hex(random_bytes(32));
    }
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $tituloSeguro ?> | Investigación</title>
        <link rel="stylesheet" href="/publico/bootstrap.min.css">
        <link rel="stylesheet" href="/publico/css/estilos.css?v=1">
    </head>

    <body class="bg-light sitio-investigacion">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary barra-superior">
            <div class="container-fluid px-lg-4">
                <a class="navbar-brand" href="/">Investigación</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navegacionInvestigacion" aria-controls="navegacionInvestigacion"
                    aria-expanded="false" aria-label="Mostrar navegación">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navegacionInvestigacion">
                    <div class="navbar-nav ms-auto flex-wrap">
                        <?php if ($sesionIniciada): ?>

                            <a class="nav-link" href="/">Inicio</a>

                            <?php if ($esAdmin || $esConsulta): ?>

                                <?php foreach ($navegacion as [$ruta, $nombre]): ?>

                                    <a class="nav-link"
                                        href="<?= htmlspecialchars($ruta, ENT_QUOTES, 'UTF-8') ?>">

                                        <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>

                                    </a>

                                <?php endforeach; ?>

                            <?php endif; ?>

                            <?php if ($esAdmin): ?>

                                <span class="navbar-text text-white ms-lg-3 small">
                                    Administrador
                                </span>

                            <?php elseif ($esConsulta): ?>

                                <span class="navbar-text text-white ms-lg-3 small">
                                    Consulta - Solo lectura
                                </span>

                            <?php endif; ?>

                        <?php endif; ?>
                        <?php if ($sesionIniciada): ?>

                            <form method="post" action="/logout"
                                class="d-flex align-items-center ms-lg-3 my-2 my-lg-0">

                                <input type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(
                                                (string) $_SESSION['csrf_logout'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                <button type="submit" class="btn btn-outline-light btn-sm">
                                    Cerrar sesión
                                </button>
                            </form>

                        <?php else: ?>

                            <a class="nav-link" href="/login">
                                Iniciar sesión
                            </a>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
        <main class="container py-4 contenido-principal">
            <?= $contenido ?>
        </main>
        <footer class="border-top bg-white py-3 mt-5 pie-sitio">
            <div class="container text-center text-muted">Módulo de Investigación</div>
        </footer>
        <script src="/publico/bootstrap.bundle.min.js"></script>
    </body>

    </html>
<?php
}
