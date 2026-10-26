<?php

declare(strict_types=1);

// Vista propia de la entidad. Se mantienen los métodos y formularios de V1.
require_once __DIR__ . '/../compartidas/plantilla.php';

/**
 * Muestra el formulario de creación y edición de líneas de investigación.
 * El ID lo genera MariaDB: no se pide al crear y no se modifica al editar.
 */
function mostrarFormularioLineaInvestigacion(
    string $modo,
    array $valores = [],
    array $errores = []
): void {
    $esEdicion = $modo === 'editar';

    $id = $valores['id'] ?? '';
    $nombre = (string) ($valores['nombre'] ?? '');
    $descripcion = (string) ($valores['descripcion'] ?? '');

    $titulo = $esEdicion
        ? 'Editar línea de investigación'
        : 'Registrar línea de investigación';

    $accion = $esEdicion
        ? '/lineas-investigacion/actualizar'
        : '/lineas-investigacion/crear';

    ob_start();
    ?>

    <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="mb-4">
                <h1 class="h2">
                    <?= htmlspecialchars(
                        $titulo,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h1>

                <p class="text-muted">
                    <?= $esEdicion
                        ? 'Modifique la información de la línea de investigación.'
                        : 'Ingrese la información de la nueva línea de investigación.'
                    ?>
                </p>
            </div>

            <?php if ($errores !== []): ?>

                <div class="alert alert-danger">
                    <strong>Revise la información ingresada:</strong>

                    <ul class="mb-0 mt-2">
                        <?php foreach ($errores as $error): ?>
                            <li>
                                <?= htmlspecialchars(
                                    (string) $error,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="<?= htmlspecialchars(
                        $accion,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">

                        <?php if ($esEdicion): ?>

                            <div class="mb-3">
                                <label for="id" class="form-label">ID</label>

                                <input
                                    type="number"
                                    id="id"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        (string) $id,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    readonly
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

                                <div class="form-text">
                                    El ID lo genera MariaDB y no puede modificarse.
                                </div>
                            </div>

                            <input
                                type="hidden"
                                name="nombre_original"
                                value="<?= htmlspecialchars(
                                    (string) ($valores['nombre_original'] ?? $nombre),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="descripcion_original"
                                value="<?= htmlspecialchars(
                                    (string) ($valores['descripcion_original'] ?? $descripcion),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="nombre" class="form-label">
                                Nombre
                            </label>

                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                class="form-control"
                                maxlength="45"
                                value="<?= htmlspecialchars(
                                    $nombre,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                required
                            >

                            <div class="form-text">
                                Máximo 45 caracteres.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="descripcion" class="form-label">
                                Descripción
                            </label>

                            <textarea
                                id="descripcion"
                                name="descripcion"
                                class="form-control"
                                rows="4"
                                maxlength="256"
                                required
                            ><?= htmlspecialchars(
                                $descripcion,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>

                            <div class="form-text">
                                Máximo 256 caracteres.
                            </div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <?php if (!$esEdicion): ?>

                                <button type="submit" class="btn btn-primary">
                                    Registrar línea
                                </button>

                            <?php else: ?>

                                <button
                                    type="submit"
                                    name="accion"
                                    value="completo"
                                    class="btn btn-primary"
                                >
                                    Guardar ficha completa
                                </button>

                                <button
                                    type="submit"
                                    name="accion"
                                    value="parcial"
                                    class="btn btn-outline-primary"
                                >
                                    Guardar solo los cambios
                                </button>

                            <?php endif; ?>

                            <a
                                href="/lineas-investigacion"
                                class="btn btn-outline-secondary"
                            >
                                Cancelar
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
    $contenido = (string) ob_get_clean();

    renderizarPlantilla(
        $titulo,
        $contenido
    );
}
