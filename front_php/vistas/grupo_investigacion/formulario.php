<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/lista.php';

/** Formulario para POST, PUT y PATCH mediante el cliente HTTP del frontend. */
function mostrarFormularioGrupoInvestigacion(
    string $modo,
    array $valores = [],
    array $errores = [],
    array $universidades = [],
    bool $catalogoDisponible = true
): void {
    $esEdicion = $modo === 'editar';
    $titulo = $esEdicion ? 'Editar grupo de investigación' : 'Registrar grupo de investigación';
    $accion = $esEdicion ? '/grupos-investigacion/actualizar' : '/grupos-investigacion/crear';
    $campos = [
        'id' => '',
        'nombre' => '',
        'url_gruplac' => '',
        'categoria' => '',
        'convocatoria' => '',
        'fecha_fundacion' => '',
        'universidsad' => '',
        'interno' => '1',
        'ambito' => '',
    ];
    foreach ($campos as $nombre => $_) {
        if (array_key_exists($nombre, $valores)) {
            $campos[$nombre] = $valores[$nombre] === null ? '' : (string) $valores[$nombre];
        }
    }

    $universidadSeleccionada = $campos['universidsad'];
    $universidadEncontrada = $universidadSeleccionada === '';
    foreach ($universidades as $universidad) {
        if (is_array($universidad) && (string) ($universidad['id'] ?? '') === $universidadSeleccionada) {
            $universidadEncontrada = true;
        }
    }

    ob_start();
    ?>
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="mb-4">
                <h1 class="h2"><?= escaparGrupoInvestigacion($titulo) ?></h1>
                <p class="text-muted">Los campos marcados con * son obligatorios.</p>
            </div>

            <?php if ($errores !== []): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Revise la información:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errores as $error): ?>
                            <li><?= escaparGrupoInvestigacion($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!$catalogoDisponible): ?>
                <div class="alert alert-warning" role="alert">
                    No fue posible consultar las universidades activas. El formulario se muestra,
                    pero debe restablecer la conexión con la API antes de guardar.
                </div>
            <?php endif; ?>

            <div class="card shadow-sm"><div class="card-body p-4">
                <form method="post" action="<?= escaparGrupoInvestigacion($accion) ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="grupo-id" class="form-label">ID *</label>
                            <input id="grupo-id" name="id" type="number" step="1" min="1"
                                   class="form-control" required
                                   value="<?= escaparGrupoInvestigacion($campos['id']) ?>"
                                   <?= $esEdicion ? 'readonly' : '' ?>>
                            <?php if ($esEdicion): ?><div class="form-text">La clave primaria no se puede cambiar.</div><?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <label for="grupo-nombre" class="form-label">Nombre *</label>
                            <input id="grupo-nombre" name="nombre" class="form-control" maxlength="60" required
                                   value="<?= escaparGrupoInvestigacion($campos['nombre']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="grupo-url" class="form-label">URL GrupLAC</label>
                            <input id="grupo-url" name="url_gruplac" class="form-control" maxlength="128"
                                   value="<?= escaparGrupoInvestigacion($campos['url_gruplac']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="grupo-categoria" class="form-label">Categoría</label>
                            <input id="grupo-categoria" name="categoria" class="form-control" maxlength="10"
                                   value="<?= escaparGrupoInvestigacion($campos['categoria']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="grupo-convocatoria" class="form-label">Convocatoria</label>
                            <input id="grupo-convocatoria" name="convocatoria" class="form-control" maxlength="10"
                                   value="<?= escaparGrupoInvestigacion($campos['convocatoria']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="grupo-fecha" class="form-label">Fecha de fundación *</label>
                            <input id="grupo-fecha" name="fecha_fundacion" type="date" class="form-control" required
                                   value="<?= escaparGrupoInvestigacion($campos['fecha_fundacion']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="grupo-universidad" class="form-label">Universidad</label>
                            <select id="grupo-universidad" name="universidsad" class="form-select"
                                    <?= $catalogoDisponible ? '' : 'disabled' ?>>
                                <option value="">Sin universidad</option>
                                <?php if (!$universidadEncontrada): ?>
                                    <option value="<?= escaparGrupoInvestigacion($universidadSeleccionada) ?>" selected>
                                        ID <?= escaparGrupoInvestigacion($universidadSeleccionada) ?> (no disponible en el catálogo activo)
                                    </option>
                                <?php endif; ?>
                                <?php foreach ($universidades as $universidad): ?>
                                    <?php if (!is_array($universidad) || !isset($universidad['id'])) { continue; } ?>
                                    <option value="<?= escaparGrupoInvestigacion($universidad['id']) ?>"
                                        <?= (string) $universidad['id'] === $universidadSeleccionada ? 'selected' : '' ?>>
                                        <?= escaparGrupoInvestigacion($universidad['nombre'] ?? 'Universidad') ?>
                                        (ID <?= escaparGrupoInvestigacion($universidad['id']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Relación opcional: solo puede asignar una universidad activa.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="grupo-interno" class="form-label">¿Grupo interno? *</label>
                            <select id="grupo-interno" name="interno" class="form-select" required>
                                <option value="1" <?= $campos['interno'] === '1' ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= $campos['interno'] === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label for="grupo-ambito" class="form-label">Ámbito *</label>
                            <input id="grupo-ambito" name="ambito" class="form-control" maxlength="45" required
                                   value="<?= escaparGrupoInvestigacion($campos['ambito']) ?>">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2 flex-wrap">
                        <?php if ($esEdicion): ?>
                            <button type="submit" class="btn btn-primary" name="accion" value="completo"
                                    <?= $catalogoDisponible ? '' : 'disabled' ?>>Guardar todo (PUT)</button>
                            <button type="submit" class="btn btn-outline-primary" name="accion" value="parcial"
                                    <?= $catalogoDisponible ? '' : 'disabled' ?>>Guardar cambios (PATCH)</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary"
                                    <?= $catalogoDisponible ? '' : 'disabled' ?>>Crear grupo</button>
                        <?php endif; ?>
                        <a href="/grupos-investigacion" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div></div>
        </div>
    </div>
    <?php
    renderizarPlantilla($titulo, (string) ob_get_clean());
}
