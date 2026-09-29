<?php

declare(strict_types=1);

/**
 * Renderiza la estructura general de las pantallas del frontend.
 *
 * $titulo: título que aparecerá en la pestaña del navegador.
 * $contenido: HTML generado por la vista correspondiente.
 */
function renderizarPlantilla(
    string $titulo,
    string $contenido
): void {
    $tituloSeguro = htmlspecialchars(
        $titulo,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?= $tituloSeguro ?> | Investigación
    </title>

    <link
        rel="stylesheet"
        href="/publico/bootstrap.min.css"
    >
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">

            <a
                class="navbar-brand"
                href="/"
            >
                Investigación
            </a>

            <div class="navbar-nav">

                <a
                    class="nav-link"
                    href="/"
                >
                    Inicio
                </a>

                <a
                    class="nav-link"
                    href="/terminos-clave"
                >
                    Términos clave
                </a>

            </div>

        </div>
    </nav>

    <main class="container py-4">

        <?= $contenido ?>

    </main>

    <footer class="border-top bg-white py-3 mt-5">
        <div class="container text-center text-muted">

            Módulo de Investigación

        </div>
    </footer>

    <script src="/publico/bootstrap.bundle.min.js"></script>

</body>

</html>
<?php
}