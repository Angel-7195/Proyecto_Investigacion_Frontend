<?php

declare(strict_types=1);

require_once __DIR__ . '/plantilla.php';

/**
 * Muestra el formulario para crear o editar un término clave.
 *
 * $modo puede ser:
 * - crear
 * - editar
 */
function mostrarFormularioTerminoClave(
    string $modo,
    array $valores = [],
    array $errores = []
): void {
    $esEdicion = $modo === 'editar';

    $termino = (string) ($valores['termino'] ?? '');

    $terminoIngles = $valores['termino_ingles'] ?? '';

    if ($terminoIngles === null) {
        $terminoIngles = '';
    }

    $titulo = $esEdicion
        ? 'Editar término clave'
        : 'Registrar término clave';

    $accion = $esEdicion
        ? '/terminos-clave/actualizar'
        : '/terminos-clave/crear';

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
                        ? 'Modifique la información del término clave.'
                        : 'Ingrese la información del nuevo término clave.'
                    ?>
                </p>

            </div>

            <?php if ($errores !== []): ?>

                <div class="alert alert-danger">

                    <strong>
                        Revise la información ingresada:
                    </strong>

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

                    <form
                        method="POST"
                        action="<?= htmlspecialchars(
                            $accion,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                        <?php if ($esEdicion): ?>

                            <input
                                type="hidden"
                                name="termino_ingles_original"
                                value="<?= htmlspecialchars(
                                    (string) $terminoIngles,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                        <?php endif; ?>

                        <div class="mb-3">

                            <label
                                for="termino"
                                class="form-label"
                            >
                                Término
                            </label>

                            <input
                                type="text"
                                id="termino"
                                name="termino"
                                class="form-control"
                                maxlength="30"
                                value="<?= htmlspecialchars(
                                    $termino,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?= $esEdicion ? 'readonly' : 'required' ?>
                            >

                            <?php if ($esEdicion): ?>

                                <div class="form-text">
                                    El término identifica el registro y no puede modificarse.
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="mb-4">

                            <label
                                for="termino_ingles"
                                class="form-label"
                            >
                                Término en inglés
                            </label>

                            <input
                                type="text"
                                id="termino_ingles"
                                name="termino_ingles"
                                class="form-control"
                                maxlength="30"
                                value="<?= htmlspecialchars(
                                    (string) $terminoIngles,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                            <div class="form-text">
                                Este campo es opcional.
                            </div>

                        </div>

                        <div class="d-flex gap-2 flex-wrap">

                            <?php if (!$esEdicion): ?>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    Registrar término
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
                                href="/terminos-clave"
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


/**
 * Muestra el formulario para crear o editar una universidad.
 *
 * $modo puede ser:
 * - crear
 * - editar
 */
function mostrarFormularioUniversidad(
    string $modo,
    array $valores = [],
    array $errores = []
): void {
    $esEdicion = $modo === 'editar';

    $id = $valores['id'] ?? '';
    $nombre = (string) ($valores['nombre'] ?? '');
    $tipo = (string) ($valores['tipo'] ?? '');
    $ciudad = (string) ($valores['ciudad'] ?? '');

    $titulo = $esEdicion
        ? 'Editar universidad'
        : 'Registrar universidad';

    $accion = $esEdicion
        ? '/universidades/actualizar'
        : '/universidades/crear';

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
                        ? 'Modifique la información de la universidad.'
                        : 'Ingrese la información de la nueva universidad.'
                    ?>
                </p>

            </div>

            <?php if ($errores !== []): ?>

                <div class="alert alert-danger">

                    <strong>
                        Revise la información ingresada:
                    </strong>

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

                    <form
                        method="POST"
                        action="<?= htmlspecialchars(
                            $accion,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                        <?php if ($esEdicion): ?>

                            <input
                                type="hidden"
                                name="nombre_original"
                                value="<?= htmlspecialchars(
                                    $nombre,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="tipo_original"
                                value="<?= htmlspecialchars(
                                    $tipo,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="ciudad_original"
                                value="<?= htmlspecialchars(
                                    $ciudad,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                        <?php endif; ?>

                        <div class="mb-3">

                            <label
                                for="id"
                                class="form-label"
                            >
                                ID
                            </label>

                            <input
                                type="number"
                                id="id"
                                name="id"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    (string) $id,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?= $esEdicion ? 'readonly' : 'required' ?>
                            >

                            <?php if ($esEdicion): ?>

                                <div class="form-text">
                                    El ID identifica la universidad y no puede modificarse.
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="mb-3">

                            <label
                                for="nombre"
                                class="form-label"
                            >
                                Nombre
                            </label>

                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                class="form-control"
                                maxlength="60"
                                value="<?= htmlspecialchars(
                                    $nombre,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label
                                for="tipo"
                                class="form-label"
                            >
                                Tipo
                            </label>

                            <input
                                type="text"
                                id="tipo"
                                name="tipo"
                                class="form-control"
                                maxlength="45"
                                value="<?= htmlspecialchars(
                                    $tipo,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                required
                            >

                        </div>

                        <div class="mb-4">

                            <label
                                for="ciudad"
                                class="form-label"
                            >
                                Ciudad
                            </label>

                            <input
                                type="text"
                                id="ciudad"
                                name="ciudad"
                                class="form-control"
                                maxlength="45"
                                value="<?= htmlspecialchars(
                                    $ciudad,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                required
                            >

                        </div>

                        <div class="d-flex gap-2 flex-wrap">

                            <?php if (!$esEdicion): ?>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    Registrar universidad
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
                                href="/universidades"
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