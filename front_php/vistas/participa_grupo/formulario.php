<?php

declare(strict_types=1);

require_once __DIR__ . '/lista.php';

/** Formulario de asociación con clave primaria compuesta. */
function mostrarFormularioParticipaGrupo(
    string $modo,
    array $valores = [],
    array $errores = [],
    array $docentes = [],
    array $grupos = [],
    bool $docentesDisponibles = true,
    bool $gruposDisponibles = true
): void {
    $esEdicion = $modo === 'editar';
    $titulo = $esEdicion ? 'Editar participación en grupo' : 'Registrar participación en grupo';
    $accion = $esEdicion ? '/participaciones-grupo/actualizar' : '/participaciones-grupo/crear';
    $campos = [
        'docente_cedula' => '',
        'grupo_investigacion_id' => '',
        'rol' => '',
        'fecha_inicio' => '',
        'fecha_fin' => '',
    ];
    foreach ($campos as $campo => $_) {
        if (array_key_exists($campo, $valores)) {
            $campos[$campo] = $valores[$campo] === null ? '' : (string) $valores[$campo];
        }
    }

    $puedeCrear = $docentesDisponibles && $gruposDisponibles
        && $docentes !== [] && $grupos !== [];
    $nombresDocentes = [];
    foreach ($docentes as $d) {
        if (is_array($d) && isset($d['cedula'])) {
            $nombresDocentes[(string) $d['cedula']] = trim(
                (string) ($d['nombres'] ?? '') . ' ' . (string) ($d['apellidos'] ?? '')
            );
        }
    }
    $nombresGrupos = [];
    foreach ($grupos as $g) {
        if (is_array($g) && isset($g['id'])) {
            $nombresGrupos[(string) $g['id']] = (string) ($g['nombre'] ?? '');
        }
    }
    ob_start();
    ?>
    <div class="row justify-content-center"><div class="col-lg-9">
        <div class="mb-4">
            <h1 class="h2"><?= escaparParticipaGrupo($titulo) ?></h1>
            <p class="text-muted">Los campos marcados con * son obligatorios.</p>
        </div>
        <?php if ($errores !== []): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Revise la información:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errores as $error): ?>
                        <li><?= escaparParticipaGrupo($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!$esEdicion && (!$docentesDisponibles || !$gruposDisponibles)): ?>
            <div class="alert alert-warning">No se pudieron consultar docentes o grupos activos.
                Compruebe la conexión con la API antes de registrar la participación.</div>
        <?php elseif (!$esEdicion && ($docentes === [] || $grupos === [])): ?>
            <div class="alert alert-info">Para registrar una participación necesita al menos
                <a href="/docentes/nuevo">un docente</a> y
                <a href="/grupos-investigacion/nuevo">un grupo</a> activos.</div>
        <?php endif; ?>
        <div class="card shadow-sm"><div class="card-body p-4">
            <form method="post" action="<?= escaparParticipaGrupo($accion) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="pg-docente" class="form-label">Docente *</label>
                        <?php if ($esEdicion): ?>
                            <input type="hidden" name="docente_cedula" value="<?= escaparParticipaGrupo($campos['docente_cedula']) ?>">
                            <input id="pg-docente" type="text" class="form-control" readonly
                                   value="<?= escaparParticipaGrupo(($nombresDocentes[$campos['docente_cedula']] ?? 'Docente') . ' (' . $campos['docente_cedula'] . ')') ?>">
                        <?php else: ?>
                            <select id="pg-docente" name="docente_cedula" class="form-select" required
                                    <?= $docentesDisponibles ? '' : 'disabled' ?>>
                                <option value="">Seleccione un docente activo</option>
                                <?php foreach ($docentes as $d): ?>
                                    <?php if (!is_array($d) || !isset($d['cedula'])) { continue; } ?>
                                    <option value="<?= escaparParticipaGrupo($d['cedula']) ?>"
                                        <?= (string) $d['cedula'] === $campos['docente_cedula'] ? 'selected' : '' ?>>
                                        <?= escaparParticipaGrupo(trim((string) ($d['nombres'] ?? '') . ' ' . (string) ($d['apellidos'] ?? ''))) ?>
                                        (<?= escaparParticipaGrupo($d['cedula']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="pg-grupo" class="form-label">Grupo de investigación *</label>
                        <?php if ($esEdicion): ?>
                            <input type="hidden" name="grupo_investigacion_id" value="<?= escaparParticipaGrupo($campos['grupo_investigacion_id']) ?>">
                            <input id="pg-grupo" type="text" class="form-control" readonly
                                   value="<?= escaparParticipaGrupo(($nombresGrupos[$campos['grupo_investigacion_id']] ?? 'Grupo') . ' (ID ' . $campos['grupo_investigacion_id'] . ')') ?>">
                        <?php else: ?>
                            <select id="pg-grupo" name="grupo_investigacion_id" class="form-select" required
                                    <?= $gruposDisponibles ? '' : 'disabled' ?>>
                                <option value="">Seleccione un grupo activo</option>
                                <?php foreach ($grupos as $g): ?>
                                    <?php if (!is_array($g) || !isset($g['id'])) { continue; } ?>
                                    <option value="<?= escaparParticipaGrupo($g['id']) ?>"
                                        <?= (string) $g['id'] === $campos['grupo_investigacion_id'] ? 'selected' : '' ?>>
                                        <?= escaparParticipaGrupo($g['nombre'] ?? 'Grupo') ?>
                                        (ID <?= escaparParticipaGrupo($g['id']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <?php if ($esEdicion): ?>
                        <div class="col-12"><div class="form-text">
                            Docente y grupo forman la clave primaria compuesta; no se pueden modificar al editar.
                        </div></div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <label for="pg-rol" class="form-label">Rol *</label>
                        <input id="pg-rol" name="rol" type="text" maxlength="15" required
                               class="form-control" value="<?= escaparParticipaGrupo($campos['rol']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="pg-inicio" class="form-label">Fecha de inicio *</label>
                        <input id="pg-inicio" name="fecha_inicio" type="date" required
                               class="form-control" value="<?= escaparParticipaGrupo($campos['fecha_inicio']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="pg-fin" class="form-label">Fecha de finalización</label>
                        <input id="pg-fin" name="fecha_fin" type="date"
                               class="form-control" value="<?= escaparParticipaGrupo($campos['fecha_fin']) ?>">
                        <div class="form-text">Opcional. Debe ser igual o posterior al inicio.</div>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2 flex-wrap">
                    <?php if ($esEdicion): ?>
                        <button type="submit" name="accion" value="completo" class="btn btn-primary">Guardar todo (PUT)</button>
                        <button type="submit" name="accion" value="parcial" class="btn btn-outline-primary">Guardar cambios (PATCH)</button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary"
                                <?= $puedeCrear ? '' : 'disabled' ?>>Crear participación</button>
                    <?php endif; ?>
                    <a href="/participaciones-grupo" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div></div>
    </div></div>
    <?php
    renderizarPlantilla($titulo, (string) ob_get_clean());
}
