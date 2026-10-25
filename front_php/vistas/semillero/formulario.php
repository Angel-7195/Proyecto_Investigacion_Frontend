<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/lista.php';

/** Formulario HTML para crear y editar semilleros a través de la API HTTP. */
function mostrarFormularioSemillero(
    string $modo,
    array $valores = [],
    array $errores = [],
    array $grupos = [],
    bool $catalogoDisponible = true
): void {
    $esEdicion = $modo === 'editar';
    $titulo = $esEdicion ? 'Editar semillero' : 'Registrar semillero';
    $accion = $esEdicion ? '/semilleros/actualizar' : '/semilleros/crear';
    $campos = [
        'id' => '',
        'nombre' => '',
        'fecha_fundacion' => '',
        'grupo_investigacion' => '',
    ];
    foreach ($campos as $nombre => $_) {
        if (array_key_exists($nombre, $valores)) {
            $campos[$nombre] = $valores[$nombre] === null ? '' : (string) $valores[$nombre];
        }
    }

    $grupoSeleccionado = $campos['grupo_investigacion'];
    $grupoEncontrado = false;
    foreach ($grupos as $grupo) {
        if (is_array($grupo) && (string) ($grupo['id'] ?? '') === $grupoSeleccionado) {
            $grupoEncontrado = true;
            break;
        }
    }
    $puedeGuardar = $catalogoDisponible && ($grupos !== []);

    ob_start();
    ?>
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="mb-4">
                <h1 class="h2"><?= escaparSemillero($titulo) ?></h1>
                <p class="text-muted">Los campos marcados con * son obligatorios.</p>
            </div>

            <?php if ($errores !== []): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Revise la información:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errores as $error): ?>
                            <li><?= escaparSemillero($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!$catalogoDisponible): ?>
                <div class="alert alert-warning" role="alert">
                    No fue posible consultar los grupos activos. Revise la conexión con la API
                    antes de guardar el formulario.
                </div>
            <?php elseif ($grupos === []): ?>
                <div class="alert alert-info" role="status">
                    No hay grupos de investigación activos. Registre primero un
                    <a href="/grupos-investigacion/nuevo">grupo de investigación</a> para asociar el semillero.
                </div>
            <?php endif; ?>

            <div class="card shadow-sm"><div class="card-body p-4">
                <form method="post" action="<?= escaparSemillero($accion) ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="semillero-id" class="form-label">ID *</label>
                            <input id="semillero-id" name="id" type="number" step="1" min="1"
                                   class="form-control" required
                                   value="<?= escaparSemillero($campos['id']) ?>"
                                   <?= $esEdicion ? 'readonly' : '' ?>>
                            <?php if ($esEdicion): ?>
                                <div class="form-text">El identificador no puede modificarse.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <label for="semillero-nombre" class="form-label">Nombre *</label>
                            <input id="semillero-nombre" name="nombre" class="form-control"
                                   maxlength="60" required value="<?= escaparSemillero($campos['nombre']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="semillero-fecha" class="form-label">Fecha de fundación *</label>
                            <input id="semillero-fecha" name="fecha_fundacion" type="date"
                                   class="form-control" required
                                   value="<?= escaparSemillero($campos['fecha_fundacion']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="semillero-grupo" class="form-label">Grupo de investigación *</label>
                            <select id="semillero-grupo" name="grupo_investigacion" class="form-select"
                                    required <?= $catalogoDisponible ? '' : 'disabled' ?>>
                                <option value="">Seleccione un grupo activo</option>
                                <?php if ($grupoSeleccionado !== '' && !$grupoEncontrado): ?>
                                    <option value="<?= escaparSemillero($grupoSeleccionado) ?>" selected disabled>
                                        Grupo ID <?= escaparSemillero($grupoSeleccionado) ?> (no está activo)
                                    </option>
                                <?php endif; ?>
                                <?php foreach ($grupos as $grupo): ?>
                                    <?php if (!is_array($grupo) || !isset($grupo['id'])) { continue; } ?>
                                    <option value="<?= escaparSemillero($grupo['id']) ?>"
                                        <?= (string) $grupo['id'] === $grupoSeleccionado ? 'selected' : '' ?>>
                                        <?= escaparSemillero($grupo['nombre'] ?? 'Grupo de investigación') ?>
                                        (ID <?= escaparSemillero($grupo['id']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Esta relación es obligatoria y el grupo debe estar activo.</div>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2 flex-wrap">
                        <?php if ($esEdicion): ?>
                            <button type="submit" class="btn btn-primary" name="accion" value="completo"
                                    <?= $puedeGuardar ? '' : 'disabled' ?>>Guardar todo (PUT)</button>
                            <button type="submit" class="btn btn-outline-primary" name="accion" value="parcial"
                                    <?= $puedeGuardar ? '' : 'disabled' ?>>Guardar cambios (PATCH)</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary"
                                    <?= $puedeGuardar ? '' : 'disabled' ?>>Crear semillero</button>
                        <?php endif; ?>
                        <a href="/semilleros" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div></div>
        </div>
    </div>
    <?php
    renderizarPlantilla($titulo, (string) ob_get_clean());
}
