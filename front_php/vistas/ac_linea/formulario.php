<?php

declare(strict_types=1);

require_once __DIR__ . '/lista.php';

/** Presenta los dos desplegables de creación; la API valida las referencias. */
function mostrarFormularioAcLinea(
    array $valores = [],
    array $errores = [],
    array $lineas = [],
    array $areas = [],
    bool $lineasDisponibles = true,
    bool $areasDisponibles = true
): void {
    $lineaElegida = (string) ($valores['linea_investigacion'] ?? '');
    $areaElegida = (string) ($valores['area_conocimiento'] ?? '');
    $puedeCrear = $lineasDisponibles && $areasDisponibles
        && $lineas !== [] && $areas !== [];

    // Mantiene juntas las disciplinas que pertenecen a la misma gran área.
    $areasOrdenadas = array_values(array_filter($areas, 'is_array'));
    usort($areasOrdenadas, static function (array $a, array $b): int {
        $campoA = implode(' · ', [
            (string) ($a['gran_area'] ?? ''),
            (string) ($a['area'] ?? ''),
            (string) ($a['disciplina'] ?? ''),
            (string) ($a['id'] ?? ''),
        ]);
        $campoB = implode(' · ', [
            (string) ($b['gran_area'] ?? ''),
            (string) ($b['area'] ?? ''),
            (string) ($b['disciplina'] ?? ''),
            (string) ($b['id'] ?? ''),
        ]);
        return strcasecmp($campoA, $campoB);
    });

    ob_start();
    ?>
    <div class="row justify-content-center"><div class="col-lg-9">
        <div class="mb-4">
            <h1 class="h2">Registrar área de conocimiento en línea</h1>
            <p class="text-muted">Seleccione una línea de investigación y un área de conocimiento activas.</p>
        </div>
        <?php if ($errores !== []): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Revise la información:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errores as $error): ?>
                        <li><?= escaparAcLinea($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!$lineasDisponibles || !$areasDisponibles): ?>
            <div class="alert alert-warning">No fue posible consultar líneas o áreas activas. Verifique la API.</div>
        <?php elseif ($lineas === [] || $areas === []): ?>
            <div class="alert alert-info">Para registrar una asociación necesita al menos
                <a href="/lineas-investigacion/nuevo">una línea</a> y
                <a href="/areas-conocimiento/nuevo">un área de conocimiento</a> activas.</div>
        <?php endif; ?>

        <div class="card shadow-sm"><div class="card-body p-4">
            <form method="post" action="/asociaciones-ac-linea/crear">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="ac-linea" class="form-label">Línea de investigación *</label>
                        <select id="ac-linea" name="linea_investigacion" class="form-select" required
                            <?= $lineasDisponibles ? '' : 'disabled' ?>>
                            <option value="">Seleccione una línea activa</option>
                            <?php foreach ($lineas as $linea): ?>
                                <?php if (!is_array($linea) || !isset($linea['id'])) { continue; } ?>
                                <option value="<?= escaparAcLinea($linea['id']) ?>"
                                    <?= (string) $linea['id'] === $lineaElegida ? 'selected' : '' ?>>
                                    <?= escaparAcLinea($linea['nombre'] ?? 'Línea') ?>
                                    (ID <?= escaparAcLinea($linea['id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="ac-area" class="form-label">Área de conocimiento *</label>
                        <select id="ac-area" name="area_conocimiento" class="form-select" required
                            <?= $areasDisponibles ? '' : 'disabled' ?>>
                            <option value="">Seleccione un área activa</option>
                            <?php $grupoAnterior = null; ?>
                            <?php foreach ($areasOrdenadas as $area): ?>
                                <?php if (!isset($area['id'])) { continue; } ?>
                                <?php $grupoActual = (string) ($area['gran_area'] ?? 'Sin gran área'); ?>
                                <?php if ($grupoActual !== $grupoAnterior): ?>
                                    <?php if ($grupoAnterior !== null): ?></optgroup><?php endif; ?>
                                    <optgroup label="<?= escaparAcLinea($grupoActual) ?>">
                                    <?php $grupoAnterior = $grupoActual; ?>
                                <?php endif; ?>
                                <option value="<?= escaparAcLinea($area['id']) ?>"
                                    <?= (string) $area['id'] === $areaElegida ? 'selected' : '' ?>>
                                    <?= escaparAcLinea($area['area'] ?? 'Área') ?> —
                                    <?= escaparAcLinea($area['disciplina'] ?? 'Disciplina') ?>
                                    (<?= escaparAcLinea($area['id']) ?>)
                                </option>
                            <?php endforeach; ?>
                            <?php if ($grupoAnterior !== null): ?></optgroup><?php endif; ?>
                        </select>
                        <div class="form-text">El identificador del área es texto (por ejemplo, 1A01).</div>
                    </div>
                </div>
                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary" <?= $puedeCrear ? '' : 'disabled' ?>>
                        Crear asociación
                    </button>
                    <a href="/asociaciones-ac-linea" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div></div>
    </div></div>
    <?php
    renderizarPlantilla('Registrar asociación', (string) ob_get_clean());
}

/** Consulta de ficha por clave compuesta: ambos IDs son de solo lectura. */
function mostrarDetalleAcLinea(array $asociacion, array $lineas = [], array $areas = []): void
{
    $lineaId = (int) ($asociacion['linea_investigacion'] ?? 0);
    $areaId = (string) ($asociacion['area_conocimiento'] ?? '');
    $nombreLinea = 'Línea de investigación';
    $nombreArea = 'Área de conocimiento';
    foreach ($lineas as $linea) {
        if (is_array($linea) && (string) ($linea['id'] ?? '') === (string) $lineaId) {
            $nombreLinea = (string) ($linea['nombre'] ?? $nombreLinea);
            break;
        }
    }
    foreach ($areas as $area) {
        if (is_array($area) && (string) ($area['id'] ?? '') === $areaId) {
            $nombreArea = implode(' · ', array_filter([
                (string) ($area['gran_area'] ?? ''),
                (string) ($area['area'] ?? ''),
                (string) ($area['disciplina'] ?? ''),
            ]));
            break;
        }
    }
    ob_start();
    ?>
    <div class="row justify-content-center"><div class="col-lg-8">
        <div class="mb-4">
            <h1 class="h2">Detalle de asociación</h1>
            <p class="text-muted">La pareja de identificadores forma la clave primaria compuesta.</p>
        </div>
        <div class="card shadow-sm"><div class="card-body p-4">
            <dl class="row mb-0">
                <dt class="col-sm-4">Línea de investigación</dt>
                <dd class="col-sm-8"><?= escaparAcLinea($nombreLinea) ?> (ID <?= escaparAcLinea($lineaId) ?>)</dd>
                <dt class="col-sm-4">Área de conocimiento</dt>
                <dd class="col-sm-8"><?= escaparAcLinea($nombreArea) ?> (<?= escaparAcLinea($areaId) ?>)</dd>
            </dl>
            <div class="alert alert-info mt-3 mb-3">Esta asociación no tiene campos editables.
                Para cambiar la relación, registre otra y retire la anterior.</div>
            <a href="/asociaciones-ac-linea" class="btn btn-outline-secondary">Volver al listado</a>
        </div></div>
    </div></div>
    <?php
    renderizarPlantilla('Detalle de asociación', (string) ob_get_clean());
}
