<?php

declare(strict_types=1);

// Vista propia de la entidad. Se mantienen los métodos y formularios de V1.
require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/../../cliente_api.php';

/**
 * Muestra el listado de líneas de investigación.
 * El frontend consulta los datos únicamente mediante la API HTTP.
 */
function mostrarListaLineasInvestigacion(): void
{
    $respuesta = listarLineasInvestigacion();

    ob_start();
    ?>

    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">

        <div>
            <h1 class="h2">Líneas de investigación</h1>

            <p class="text-muted mb-0">
                Consulte y administre las líneas de investigación registradas.
            </p>
        </div>

        <a href="/lineas-investigacion/nuevo" class="btn btn-primary">
            Registrar línea
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
            Todavía no hay líneas de investigación registradas.
        </div>

        <a href="/lineas-investigacion/nuevo" class="btn btn-primary">
            Registrar primera línea
        </a>

    <?php elseif (!$respuesta['correcta']): ?>

        <div class="alert alert-danger">
            No fue posible consultar las líneas de investigación.
        </div>

    <?php else: ?>

        <?php $registros = $respuesta['datos']['datos'] ?? []; ?>

        <?php if ($registros === []): ?>

            <div class="alert alert-info">
                Todavía no hay líneas de investigación registradas.
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($registros as $registro): ?>

                            <?php
                            $id = (int) ($registro['id'] ?? 0);
                            $nombre = (string) ($registro['nombre'] ?? '');
                            $descripcion = (string) ($registro['descripcion'] ?? '');
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
                                        $descripcion,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td class="text-end">
                                    <a
                                        href="/lineas-investigacion/editar?id=<?= rawurlencode((string) $id) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        method="POST"
                                        action="/lineas-investigacion/retirar"
                                        class="d-inline"
                                        onsubmit="return confirm('¿Desea retirar esta línea de investigación?');"
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
        'Líneas de investigación',
        $contenido
    );
}
