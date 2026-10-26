<?php

declare(strict_types=1);

require_once __DIR__ . '/lista.php';

/** Formulario de asociación con clave primaria compuesta. */
function mostrarFormularioParticipaSemillero(
    string $modo,
    array $valores = [],
    array $errores = [],
    array $docentes = [],
    array $semilleros = [],
    bool $docentesDisponibles = true,
    bool $semillerosDisponibles = true
): void {
    $esEdicion = $modo === 'editar';
    $titulo = $esEdicion ? 'Editar participación en semillero' : 'Registrar participación en semillero';
    $accion = $esEdicion ? '/participaciones-semillero/actualizar' : '/participaciones-semillero/crear';
    $campos = [
        'docente' => '',
        'semillero' => '',
        'rol' => '',
        'fecha_inicio' => '',
        'fecha_fin' => '',
    ];
    foreach ($campos as $campo => $_) {
        if (array_key_exists($campo, $valores)) {
            $campos[$campo] = $valores[$campo] === null ? '' : (string) $valores[$campo];
        }
    }

    $puedeCrear = $docentesDisponibles && $semillerosDisponibles
        && $docentes !== [] && $semilleros !== [];
    $nombresDocentes = [];
    foreach ($docentes as $d) {
        if (is_array($d) && isset($d['cedula'])) {
            $nombresDocentes[(string) $d['cedula']] = trim(
                (string) ($d['nombres'] ?? '') . ' ' . (string) ($d['apellidos'] ?? '')
            );
        }
    }
    $nombresSemilleros = [];
    foreach ($semilleros as $g) {
        if (is_array($g) && isset($g['id'])) {
            $nombresSemilleros[(string) $g['id']] = (string) ($g['nombre'] ?? '');
        }
    }
    ob_start();
    ?>
    <div class="row justify-content-center"><div class="col-lg-9">
        <div class="mb-4">
            <h1 class="h2"><?= escaparParticipaSemillero($titulo) ?></h1>
            <p class="text-muted">Los campos marcados con * son obligatorios.</p>
        </div>
        <?php if ($errores !== []): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Revise la información:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errores as $error): ?>
                        <li><?= escaparParticipaSemillero($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!$esEdicion && (!$docentesDisponibles || !$semillerosDisponibles)): ?>
            <div class="alert alert-warning">No se pudieron consultar docentes o semilleros activos.
                Compruebe la conexión con la API antes de registrar la participación.</div>
        <?php elseif (!$esEdicion && ($docentes === [] || $semilleros === [])): ?>
            <div class="alert alert-info">Para registrar una participación necesita al menos
                <a href="/docentes/nuevo">un docente</a> y
                <a href="/semilleros/nuevo">un semillero</a> activos.</div>
        <?php endif; ?>
        <div class="card shadow-sm"><div class="card-body p-4">
            <form method="post" action="<?= escaparParticipaSemillero($accion) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="ps-docente" class="form-label">Docente *</label>
                        <?php if ($esEdicion): ?>
                            <input type="hidden" name="docente" value="<?= escaparParticipaSemillero($campos['docente']) ?>">
                            <input id="ps-docente" type="text" class="form-control" readonly
                                   value="<?= escaparParticipaSemillero(($nombresDocentes[$campos['docente']] ?? 'Docente') . ' (' . $campos['docente'] . ')') ?>">
                        <?php else: ?>
                            <select id="ps-docente" name="docente" class="form-select" required
                                    <?= $docentesDisponibles ? '' : 'disabled' ?>>
                                <option value="">Seleccione un docente activo</option>
                                <?php foreach ($docentes as $d): ?>
                                    <?php if (!is_array($d) || !isset($d['cedula'])) { continue; } ?>
                                    <option value="<?= escaparParticipaSemillero($d['cedula']) ?>"
                                        <?= (string) $d['cedula'] === $campos['docente'] ? 'selected' : '' ?>>
                                        <?= escaparParticipaSemillero(trim((string) ($d['nombres'] ?? '') . ' ' . (string) ($d['apellidos'] ?? ''))) ?>
                                        (<?= escaparParticipaSemillero($d['cedula']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="ps-semillero" class="form-label">Semillero de investigación *</label>
                        <?php if ($esEdicion): ?>
                            <input type="hidden" name="semillero" value="<?= escaparParticipaSemillero($campos['semillero']) ?>">
                            <input id="ps-semillero" type="text" class="form-control" readonly
                                   value="<?= escaparParticipaSemillero(($nombresSemilleros[$campos['semillero']] ?? 'Semillero') . ' (ID ' . $campos['semillero'] . ')') ?>">
                        <?php else: ?>
                            <select id="ps-semillero" name="semillero" class="form-select" required
                                    <?= $semillerosDisponibles ? '' : 'disabled' ?>>
                                <option value="">Seleccione un semillero activo</option>
                                <?php foreach ($semilleros as $g): ?>
                                    <?php if (!is_array($g) || !isset($g['id'])) { continue; } ?>
                                    <option value="<?= escaparParticipaSemillero($g['id']) ?>"
                                        <?= (string) $g['id'] === $campos['semillero'] ? 'selected' : '' ?>>
                                        <?= escaparParticipaSemillero($g['nombre'] ?? 'Semillero') ?>
                                        (ID <?= escaparParticipaSemillero($g['id']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <?php if ($esEdicion): ?>
                        <div class="col-12"><div class="form-text">
                            Docente y semillero forman la clave primaria compuesta; no se pueden modificar al editar.
                        </div></div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <label for="ps-rol" class="form-label">Rol *</label>
                        <input id="ps-rol" name="rol" type="text" maxlength="15" required
                               class="form-control" value="<?= escaparParticipaSemillero($campos['rol']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="ps-inicio" class="form-label">Fecha de inicio *</label>
                        <input id="ps-inicio" name="fecha_inicio" type="date" required
                               class="form-control" value="<?= escaparParticipaSemillero($campos['fecha_inicio']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="ps-fin" class="form-label">Fecha de finalización</label>
                        <input id="ps-fin" name="fecha_fin" type="date"
                               class="form-control" value="<?= escaparParticipaSemillero($campos['fecha_fin']) ?>">
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
                    <a href="/participaciones-semillero" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div></div>
    </div></div>
    <?php
    renderizarPlantilla($titulo, (string) ob_get_clean());
}
