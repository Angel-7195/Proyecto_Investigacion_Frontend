<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/lista.php';

/** Formulario de docente; la validación final y la FK corresponden a la API. */
function mostrarFormularioDocente(
    string $modo,
    array $valores = [],
    array $errores = [],
    array $lineas = [],
    bool $catalogoDisponible = true
): void {
    $esEdicion = $modo === 'editar';
    $titulo = $esEdicion ? 'Editar docente' : 'Registrar docente';
    $accion = $esEdicion ? '/docentes/actualizar' : '/docentes/crear';
    $campos = [
        'cedula' => '',
        'nombres' => '', 'apellidos' => '', 'genero' => '', 'cargo' => '',
        'fecha_nacimiento' => '', 'correo' => '', 'telefono' => '',
        'url_cvlac' => '', 'fecha_actualizacion' => '', 'escalafon' => '',
        'perfil' => '', 'cat_minciencia' => '', 'conv_minciencia' => '',
        'nacionalidaad' => '', 'linea_investigacion_principal' => '',
    ];
    foreach ($campos as $campo => $_) {
        if (array_key_exists($campo, $valores)) {
            $campos[$campo] = $valores[$campo] === null ? '' : (string) $valores[$campo];
        }
    }
    $lineaSeleccionada = $campos['linea_investigacion_principal'];
    $lineaEncontrada = $lineaSeleccionada === '';
    foreach ($lineas as $linea) {
        if (is_array($linea) && (string) ($linea['id'] ?? '') === $lineaSeleccionada) {
            $lineaEncontrada = true;
            break;
        }
    }

    ob_start();
    ?>
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="mb-4">
                <h1 class="h2"><?= escaparDocente($titulo) ?></h1>
                <p class="text-muted">Los campos marcados con * son obligatorios. La línea principal es opcional.</p>
            </div>

            <?php if ($errores !== []): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Revise la información:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errores as $error): ?>
                            <li><?= escaparDocente($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if (!$catalogoDisponible): ?>
                <div class="alert alert-warning" role="alert">
                    No fue posible consultar las líneas activas. Compruebe que la API esté funcionando antes de guardar.
                </div>
            <?php elseif ($lineas === []): ?>
                <div class="alert alert-info" role="status">
                    No hay líneas de investigación activas. Puede registrar un docente sin línea principal.
                </div>
            <?php endif; ?>

            <div class="card shadow-sm"><div class="card-body p-4">
                <form method="post" action="<?= escaparDocente($accion) ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="docente-cedula" class="form-label">Cédula *</label>
                            <input id="docente-cedula" name="cedula" type="number" min="1" max="2147483647"
                                   step="1" class="form-control" required
                                   value="<?= escaparDocente($campos['cedula']) ?>"
                                   <?= $esEdicion ? 'readonly' : '' ?>>
                            <?php if ($esEdicion): ?><div class="form-text">La cédula no se puede modificar.</div><?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="docente-nombres" class="form-label">Nombres *</label>
                            <input id="docente-nombres" name="nombres" class="form-control" maxlength="60" required
                                   value="<?= escaparDocente($campos['nombres']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="docente-apellidos" class="form-label">Apellidos *</label>
                            <input id="docente-apellidos" name="apellidos" class="form-control" maxlength="60" required
                                   value="<?= escaparDocente($campos['apellidos']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="docente-genero" class="form-label">Género *</label>
                            <input id="docente-genero" name="genero" class="form-control" maxlength="12" required
                                   value="<?= escaparDocente($campos['genero']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="docente-cargo" class="form-label">Cargo *</label>
                            <input id="docente-cargo" name="cargo" class="form-control" maxlength="30" required
                                   value="<?= escaparDocente($campos['cargo']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="docente-fecha-nacimiento" class="form-label">Fecha de nacimiento *</label>
                            <input id="docente-fecha-nacimiento" name="fecha_nacimiento" type="date" class="form-control" required
                                   value="<?= escaparDocente($campos['fecha_nacimiento']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="docente-correo" class="form-label">Correo electrónico *</label>
                            <input id="docente-correo" name="correo" type="email" class="form-control" maxlength="70" required
                                   value="<?= escaparDocente($campos['correo']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="docente-telefono" class="form-label">Teléfono *</label>
                            <input id="docente-telefono" name="telefono" class="form-control" maxlength="20" required
                                   value="<?= escaparDocente($campos['telefono']) ?>">
                        </div>
                        <div class="col-md-8">
                            <label for="docente-url-cvlac" class="form-label">URL CvLAC *</label>
                            <input id="docente-url-cvlac" name="url_cvlac" class="form-control" maxlength="128" required
                                   value="<?= escaparDocente($campos['url_cvlac']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="docente-fecha-actualizacion" class="form-label">Fecha de actualización *</label>
                            <input id="docente-fecha-actualizacion" name="fecha_actualizacion" type="date" class="form-control" required
                                   value="<?= escaparDocente($campos['fecha_actualizacion']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="docente-escalafon" class="form-label">Escalafón *</label>
                            <input id="docente-escalafon" name="escalafon" class="form-control" maxlength="45" required
                                   value="<?= escaparDocente($campos['escalafon']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="docente-cat-minciencia" class="form-label">Categoría Minciencias</label>
                            <input id="docente-cat-minciencia" name="cat_minciencia" class="form-control" maxlength="45"
                                   value="<?= escaparDocente($campos['cat_minciencia']) ?>">
                            <div class="form-text">Campo opcional; vacío se envía como NULL.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="docente-conv-minciencia" class="form-label">Convocatoria Minciencias *</label>
                            <input id="docente-conv-minciencia" name="conv_minciencia" class="form-control" maxlength="45" required
                                   value="<?= escaparDocente($campos['conv_minciencia']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="docente-nacionalidad" class="form-label">Nacionalidad *</label>
                            <input id="docente-nacionalidad" name="nacionalidaad" class="form-control" maxlength="45" required
                                   value="<?= escaparDocente($campos['nacionalidaad']) ?>">
                            <div class="form-text">En la base de datos, la columna se llama nacionalidaad.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="docente-linea" class="form-label">Línea de investigación principal</label>
                            <select id="docente-linea" name="linea_investigacion_principal" class="form-select"
                                    <?= $catalogoDisponible ? '' : 'disabled' ?>>
                                <option value="">Sin línea principal</option>
                                <?php if ($lineaSeleccionada !== '' && !$lineaEncontrada): ?>
                                    <option value="<?= escaparDocente($lineaSeleccionada) ?>" selected disabled>
                                        Línea ID <?= escaparDocente($lineaSeleccionada) ?> (no está activa)
                                    </option>
                                <?php endif; ?>
                                <?php foreach ($lineas as $linea): ?>
                                    <?php if (!is_array($linea) || !isset($linea['id'])) { continue; } ?>
                                    <option value="<?= escaparDocente($linea['id']) ?>"
                                        <?= (string) $linea['id'] === $lineaSeleccionada ? 'selected' : '' ?>>
                                        <?= escaparDocente($linea['nombre'] ?? 'Línea de investigación') ?>
                                        (ID <?= escaparDocente($linea['id']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Opcional; si selecciona una línea, debe estar activa.</div>
                        </div>
                        <div class="col-12">
                            <label for="docente-perfil" class="form-label">Perfil *</label>
                            <textarea id="docente-perfil" name="perfil" class="form-control" rows="5" required><?= escaparDocente($campos['perfil']) ?></textarea>
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
                                    <?= $catalogoDisponible ? '' : 'disabled' ?>>Crear docente</button>
                        <?php endif; ?>
                        <a href="/docentes" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div></div>
        </div>
    </div>
    <?php
    renderizarPlantilla($titulo, (string) ob_get_clean());
}
