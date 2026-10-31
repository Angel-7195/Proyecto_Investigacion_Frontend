<?php

declare(strict_types=1);

require_once __DIR__ . '/lista.php';

/** Formulario de creación con los catálogos de padres activos. */
function mostrarFormularioOdsLinea(
    array $valores = [],
    array $errores = [],
    array $lineas = [],
    array $ods = [],
    bool $lineasDisponibles = true,
    bool $odsDisponibles = true
): void {
    $lineaElegida = (string) ($valores['linea_investigacion'] ?? '');
    $odsElegido = (string) ($valores['ods'] ?? '');
    $puedeCrear = $lineasDisponibles && $odsDisponibles
        && $lineas !== [] && $ods !== [];

    // Orden numérico para mostrar primero los 17 ODS oficiales.
    $odsOrdenados = array_values(array_filter($ods, 'is_array'));
    usort($odsOrdenados, static fn (array $a, array $b): int =>
        (int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0)
    );

    ob_start();
    ?>
    <div class="row justify-content-center"><div class="col-lg-9">
        <div class="mb-4">
            <h1 class="h2">Registrar ODS en línea de investigación</h1>
            <p class="text-muted">Seleccione una línea de investigación y un ODS activos.</p>
        </div>
        <?php if ($errores !== []): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Revise la información:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errores as $error): ?>
                        <li><?= escaparOdsLinea($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!$lineasDisponibles || !$odsDisponibles): ?>
            <div class="alert alert-warning">No fue posible consultar las líneas o los ODS activos. Verifique la API.</div>
        <?php elseif ($lineas === [] || $ods === []): ?>
            <div class="alert alert-info">Para registrar la asociación necesita al menos
                <a href="/lineas-investigacion/nuevo">una línea de investigación</a> y
                <a href="/objetivos-desarrollo-sostenible/nuevo">un ODS</a> activos.</div>
        <?php endif; ?>

        <div class="card shadow-sm"><div class="card-body p-4">
            <form method="post" action="/asociaciones-ods-linea/crear">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="ods-linea-id" class="form-label">Línea de investigación *</label>
                        <select id="ods-linea-id" name="linea_investigacion" class="form-select" required
                            <?= $lineasDisponibles ? '' : 'disabled' ?>>
                            <option value="">Seleccione una línea activa</option>
                            <?php foreach ($lineas as $linea): ?>
                                <?php if (!is_array($linea) || !isset($linea['id'])) { continue; } ?>
                                <option value="<?= escaparOdsLinea($linea['id']) ?>"
                                    <?= (string) $linea['id'] === $lineaElegida ? 'selected' : '' ?>>
                                    <?= escaparOdsLinea($linea['nombre'] ?? 'Línea') ?>
                                    (ID <?= escaparOdsLinea($linea['id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="ods-objetivo-id" class="form-label">Objetivo de Desarrollo Sostenible *</label>
                        <select id="ods-objetivo-id" name="ods" class="form-select" required
                            <?= $odsDisponibles ? '' : 'disabled' ?>>
                            <option value="">Seleccione un ODS activo</option>
                            <?php foreach ($odsOrdenados as $objetivo): ?>
                                <?php if (!isset($objetivo['id'])) { continue; } ?>
                                <option value="<?= escaparOdsLinea($objetivo['id']) ?>"
                                    <?= (string) $objetivo['id'] === $odsElegido ? 'selected' : '' ?>>
                                    ODS <?= escaparOdsLinea($objetivo['id']) ?> —
                                    <?= escaparOdsLinea($objetivo['nombre'] ?? 'Objetivo') ?>
                                    <?php if (!empty($objetivo['categoria'])): ?>
                                        (<?= escaparOdsLinea($objetivo['categoria']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary" <?= $puedeCrear ? '' : 'disabled' ?>>
                        Crear asociación
                    </button>
                    <a href="/asociaciones-ods-linea" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div></div>
    </div></div>
    <?php
    renderizarPlantilla('Registrar asociación ODS', (string) ob_get_clean());
}

/** Consulta de la pareja; ambos identificadores son de solo lectura. */
function mostrarDetalleOdsLinea(array $asociacion, array $lineas = [], array $ods = []): void
{
    $lineaId = (int) ($asociacion['linea_investigacion'] ?? 0);
    $odsId = (int) ($asociacion['ods'] ?? 0);
    $nombreLinea = 'Línea de investigación';
    $nombreOds = 'Objetivo de Desarrollo Sostenible';
    $categoriaOds = '';
    foreach ($lineas as $linea) {
        if (is_array($linea) && (string) ($linea['id'] ?? '') === (string) $lineaId) {
            $nombreLinea = (string) ($linea['nombre'] ?? $nombreLinea);
            break;
        }
    }
    foreach ($ods as $objetivo) {
        if (is_array($objetivo) && (string) ($objetivo['id'] ?? '') === (string) $odsId) {
            $nombreOds = (string) ($objetivo['nombre'] ?? $nombreOds);
            $categoriaOds = (string) ($objetivo['categoria'] ?? '');
            break;
        }
    }

    ob_start();
    ?>
    <div class="row justify-content-center"><div class="col-lg-8">
        <div class="mb-4">
            <h1 class="h2">Detalle de asociación ODS</h1>
            <p class="text-muted">Los dos identificadores forman la clave primaria compuesta.</p>
        </div>
        <div class="card shadow-sm"><div class="card-body p-4">
            <dl class="row mb-0">
                <dt class="col-sm-4">Línea de investigación</dt>
                <dd class="col-sm-8"><?= escaparOdsLinea($nombreLinea) ?> (ID <?= escaparOdsLinea($lineaId) ?>)</dd>
                <dt class="col-sm-4">ODS</dt>
                <dd class="col-sm-8">ODS <?= escaparOdsLinea($odsId) ?> — <?= escaparOdsLinea($nombreOds) ?>
                    <?php if ($categoriaOds !== ''): ?>
                        (<?= escaparOdsLinea($categoriaOds) ?>)
                    <?php endif; ?>
                </dd>
            </dl>
            <div class="alert alert-info mt-3 mb-3">Esta asociación no tiene campos editables.
                Para cambiar la relación, registre otra y retire la anterior.</div>
            <a href="/asociaciones-ods-linea" class="btn btn-outline-secondary">Volver al listado</a>
        </div></div>
    </div></div>
    <?php
    renderizarPlantilla('Detalle de asociación ODS', (string) ob_get_clean());
}
