<?php

declare(strict_types=1);

require_once __DIR__ . '/lista.php';

/** Formulario de registro con listas de las dos entidades relacionadas. */
function mostrarFormularioAaLinea(
    array $valores = [],
    array $errores = [],
    array $areas = [],
    array $lineas = [],
    bool $areasDisponibles = true,
    bool $lineasDisponibles = true
): void {
    $areaElegida = (string) ($valores['area_aplicacion'] ?? '');
    $lineaElegida = (string) ($valores['linea_investigacion'] ?? '');
    $puedeCrear = $areasDisponibles && $lineasDisponibles
        && $areas !== [] && $lineas !== [];

    // Presentación por nombre del catálogo de áreas de aplicación.
    $areasOrdenadas = array_values(array_filter($areas, 'is_array'));
    usort($areasOrdenadas, static function (array $a, array $b): int {
        return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
    });

    ob_start();
    ?>
    <div class="row justify-content-center"><div class="col-lg-9">
        <div class="mb-4">
            <h1 class="h2">Registrar área de aplicación en línea</h1>
            <p class="text-muted">Seleccione un área de aplicación y una línea de investigación activas.</p>
        </div>
        <?php if ($errores !== []): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Revise la información:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errores as $error): ?>
                        <li><?= escaparAaLinea($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!$areasDisponibles || !$lineasDisponibles): ?>
            <div class="alert alert-warning">No fue posible consultar áreas o líneas activas. Verifique la API.</div>
        <?php elseif ($areas === [] || $lineas === []): ?>
            <div class="alert alert-info">Para registrar una asociación necesita al menos
                <a href="/areas-aplicacion/nuevo">un área de aplicación</a> y
                <a href="/lineas-investigacion/nuevo">una línea de investigación</a> activas.</div>
        <?php endif; ?>

        <div class="card shadow-sm"><div class="card-body p-4">
            <form method="post" action="/asociaciones-aa-linea/crear">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="aa-area-id" class="form-label">Área de aplicación *</label>
                        <select id="aa-area-id" name="area_aplicacion" class="form-select" required
                            <?= $areasDisponibles ? '' : 'disabled' ?>>
                            <option value="">Seleccione un área activa</option>
                            <?php foreach ($areasOrdenadas as $area): ?>
                                <?php if (!isset($area['id'])) { continue; } ?>
                                <option value="<?= escaparAaLinea($area['id']) ?>"
                                    <?= (string) $area['id'] === $areaElegida ? 'selected' : '' ?>>
                                    <?= escaparAaLinea($area['nombre'] ?? 'Área de aplicación') ?>
                                    (ID <?= escaparAaLinea($area['id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="aa-linea-id" class="form-label">Línea de investigación *</label>
                        <select id="aa-linea-id" name="linea_investigacion" class="form-select" required
                            <?= $lineasDisponibles ? '' : 'disabled' ?>>
                            <option value="">Seleccione una línea activa</option>
                            <?php foreach ($lineas as $linea): ?>
                                <?php if (!is_array($linea) || !isset($linea['id'])) { continue; } ?>
                                <option value="<?= escaparAaLinea($linea['id']) ?>"
                                    <?= (string) $linea['id'] === $lineaElegida ? 'selected' : '' ?>>
                                    <?= escaparAaLinea($linea['nombre'] ?? 'Línea') ?>
                                    (ID <?= escaparAaLinea($linea['id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary" <?= $puedeCrear ? '' : 'disabled' ?>>
                        Crear asociación
                    </button>
                    <a href="/asociaciones-aa-linea" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div></div>
    </div></div>
    <?php
    renderizarPlantilla('Registrar asociación AA', (string) ob_get_clean());
}

/** Muestra los dos identificadores de la clave primaria como solo lectura. */
function mostrarDetalleAaLinea(array $asociacion, array $areas = [], array $lineas = []): void
{
    $areaId = (int) ($asociacion['area_aplicacion'] ?? 0);
    $lineaId = (int) ($asociacion['linea_investigacion'] ?? 0);
    $nombreArea = 'Área de aplicación';
    $nombreLinea = 'Línea de investigación';
    foreach ($areas as $area) {
        if (is_array($area) && (string) ($area['id'] ?? '') === (string) $areaId) {
            $nombreArea = (string) ($area['nombre'] ?? $nombreArea);
            break;
        }
    }
    foreach ($lineas as $linea) {
        if (is_array($linea) && (string) ($linea['id'] ?? '') === (string) $lineaId) {
            $nombreLinea = (string) ($linea['nombre'] ?? $nombreLinea);
            break;
        }
    }

    ob_start();
    ?>
    <div class="row justify-content-center"><div class="col-lg-8">
        <div class="mb-4">
            <h1 class="h2">Detalle de asociación</h1>
            <p class="text-muted">Los dos identificadores forman la clave primaria compuesta.</p>
        </div>
        <div class="card shadow-sm"><div class="card-body p-4">
            <dl class="row mb-0">
                <dt class="col-sm-4">Área de aplicación</dt>
                <dd class="col-sm-8"><?= escaparAaLinea($nombreArea) ?> (ID <?= escaparAaLinea($areaId) ?>)</dd>
                <dt class="col-sm-4">Línea de investigación</dt>
                <dd class="col-sm-8"><?= escaparAaLinea($nombreLinea) ?> (ID <?= escaparAaLinea($lineaId) ?>)</dd>
            </dl>
            <div class="alert alert-info mt-3 mb-3">Esta asociación no tiene campos editables.
                Para cambiar la relación, registre otra y retire la anterior.</div>
            <a href="/asociaciones-aa-linea" class="btn btn-outline-secondary">Volver al listado</a>
        </div></div>
    </div></div>
    <?php
    renderizarPlantilla('Detalle de asociación AA', (string) ob_get_clean());
}
