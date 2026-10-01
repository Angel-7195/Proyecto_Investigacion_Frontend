<?php

declare(strict_types=1);

require_once __DIR__ . '/plantilla.php';
require_once __DIR__ . '/../cliente_api.php';

/**
 * Muestra el listado de términos clave.
 */
function mostrarListaTerminosClave(): void
{
    $respuesta = listarTerminosClave();

    ob_start();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h1 class="h2">
                Términos clave
            </h1>

            <p class="text-muted mb-0">
                Consulte y administre los términos clave registrados.
            </p>
        </div>

        <a
            href="/terminos-clave/nuevo"
            class="btn btn-primary"
        >
            Registrar término
        </a>

    </div>

    <?php if (!$respuesta['disponible']): ?>

        <div class="alert alert-warning">
            El servicio no está disponible en este momento.
            Puede continuar utilizando la aplicación e intentarlo nuevamente
            más tarde.
        </div>

    <?php elseif ($respuesta['estado'] === 204): ?>

        <div class="alert alert-info">
            Todavía no hay registros.
        </div>

        <a
            href="/terminos-clave/nuevo"
            class="btn btn-primary"
        >
            Registrar primer término
        </a>

    <?php elseif (!$respuesta['correcta']): ?>

        <div class="alert alert-danger">
            No fue posible consultar los términos clave.
        </div>

    <?php else: ?>

        <?php
        $registros = $respuesta['datos']['datos'] ?? [];
        ?>

        <?php if ($registros === []): ?>

            <div class="alert alert-info">
                Todavía no hay registros.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">

                    <thead class="table-dark">

                        <tr>
                            <th>Término</th>
                            <th>Término en inglés</th>
                            <th class="text-end">Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($registros as $registro): ?>

                            <?php
                            $termino = (string) ($registro['termino'] ?? '');
                            $terminoIngles = $registro['termino_ingles'] ?? null;
                            ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $termino,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if ($terminoIngles === null): ?>

                                        <span class="text-muted">
                                            Sin traducción
                                        </span>

                                    <?php else: ?>

                                        <?= htmlspecialchars(
                                            (string) $terminoIngles,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    <?php endif; ?>
                                </td>

                                <td class="text-end">

                                    <a
                                        href="/terminos-clave/editar?termino=<?= rawurlencode($termino) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        method="POST"
                                        action="/terminos-clave/retirar"
                                        class="d-inline"
                                        onsubmit="return confirm('¿Desea retirar este término clave?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="termino"
                                            value="<?= htmlspecialchars(
                                                $termino,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                        >
                                            Retirar
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    <?php endif; ?>

    <?php
    $contenido = (string) ob_get_clean();

    renderizarPlantilla(
        'Términos clave',
        $contenido
    );
}


/**
 * Muestra el listado de universidades.
 */
function mostrarListaUniversidades(): void
{
    $respuesta = listarUniversidades();

    ob_start();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h1 class="h2">
                Universidades
            </h1>

            <p class="text-muted mb-0">
                Consulte y administre las universidades registradas.
            </p>
        </div>

        <a
            href="/universidades/nuevo"
            class="btn btn-primary"
        >
            Registrar universidad
        </a>

    </div>

    <?php if (!$respuesta['disponible']): ?>

        <div class="alert alert-warning">
            El servicio no está disponible en este momento.
            Puede continuar utilizando la aplicación e intentarlo nuevamente
            más tarde.
        </div>

    <?php elseif ($respuesta['estado'] === 204): ?>

        <div class="alert alert-info">
            Todavía no hay universidades registradas.
        </div>

        <a
            href="/universidades/nuevo"
            class="btn btn-primary"
        >
            Registrar primera universidad
        </a>

    <?php elseif (!$respuesta['correcta']): ?>

        <div class="alert alert-danger">
            No fue posible consultar las universidades.
        </div>

    <?php else: ?>

        <?php
        $registros = $respuesta['datos']['datos'] ?? [];
        ?>

        <?php if ($registros === []): ?>

            <div class="alert alert-info">
                Todavía no hay universidades registradas.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">

                    <thead class="table-dark">

                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Ciudad</th>
                            <th class="text-end">Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($registros as $registro): ?>

                            <?php
                            $id = (int) ($registro['id'] ?? 0);
                            $nombre = (string) ($registro['nombre'] ?? '');
                            $tipo = (string) ($registro['tipo'] ?? '');
                            $ciudad = (string) ($registro['ciudad'] ?? '');
                            ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $id,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $nombre,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $tipo,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $ciudad,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td class="text-end">

                                    <a
                                        href="/universidades/editar?id=<?= rawurlencode((string) $id) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        method="POST"
                                        action="/universidades/retirar"
                                        class="d-inline"
                                        onsubmit="return confirm('¿Desea retirar esta universidad?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= htmlspecialchars(
                                                (string) $id,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                        >
                                            Retirar
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    <?php endif; ?>

    <?php
    $contenido = (string) ob_get_clean();

    renderizarPlantilla(
        'Universidades',
        $contenido
    );
}