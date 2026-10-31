<?php

declare(strict_types=1);

require_once __DIR__ . '/lista.php';

/** Formulario de registro con listas de las dos entidades relacionadas. */
function mostrarFormularioSemilleroLinea(
    array $valores = [],
    array $errores = [],
    array $semilleros = [],
    array $lineas = [],
    bool $semillerosDisponibles = true,
    bool $lineasDisponibles = true
): void {
    $semilleroElegido = (string) ($valores['semillero'] ?? '');
    $lineaElegida = (string) ($valores['linea_investigacion'] ?? '');
    $puedeCrear = $semillerosDisponibles && $lineasDisponibles
        && $semilleros !== [] && $lineas !== [];

    // Presentación por nombre del catálogo de semilleros.
    $semillerosOrdenados = array_values(array_filter($semilleros, 'is_array'));
    usort($semillerosOrdenados, static function (array $a, array $b): int {
        return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
    });

    ob_start();
    ?>
    <div class="row justify-content-center"><div class="col-lg-9">
        <div class="mb-4">
            <h1 class="h2">Registrar semillero en línea de investigación</h1>
            <p class="text-muted">Seleccione un semillero y una línea de investigación activos.</p>
        </div>
        <?php if ($errores !== []): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Revise la información:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errores as $error): ?>
                        <li><?= escaparSemilleroLinea($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!$semillerosDisponibles || !$lineasDisponibles): ?>
            <div class="alert alert-warning">No fue posible consultar semilleros o líneas activas. Verifique la API.</div>
        <?php elseif ($semilleros === [] || $lineas === []): ?>
            <div class="alert alert-info">Para registrar una asociación necesita al menos
                <a href="/semilleros/nuevo">un semillero</a> y
                <a href="/lineas-investigacion/nuevo">una línea de investigación</a> activas.</div>
        <?php endif; ?>

        <div class="card shadow-sm"><div class="card-body p-4">
            <form method="post" action="/asociaciones-semillero-linea/crear">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="semillero-linea-semillero-id" class="form-label">Semillero *</label>
                        <select id="semillero-linea-semillero-id" name="semillero" class="form-select" required
                            <?= $semillerosDisponibles ? '' : 'disabled' ?>>
                            <option value="">Seleccione un semillero activo</option>
                            <?php foreach ($semillerosOrdenados as $semillero): ?>
                                <?php if (!isset($semillero['id'])) { continue; } ?>
                                <option value="<?= escaparSemilleroLinea($semillero['id']) ?>"
                                    <?= (string) $semillero['id'] === $semilleroElegido ? 'selected' : '' ?>>
                                    <?= escaparSemilleroLinea($semillero['nombre'] ?? 'Semillero') ?>
                                    (ID <?= escaparSemilleroLinea($semillero['id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="semillero-linea-linea-id" class="form-label">Línea de investigación *</label>
                        <select id="semillero-linea-linea-id" name="linea_investigacion" class="form-select" required
                            <?= $lineasDisponibles ? '' : 'disabled' ?>>
                            <option value="">Seleccione una línea activa</option>
                            <?php foreach ($lineas as $linea): ?>
                                <?php if (!is_array($linea) || !isset($linea['id'])) { continue; } ?>
                                <option value="<?= escaparSemilleroLinea($linea['id']) ?>"
                                    <?= (string) $linea['id'] === $lineaElegida ? 'selected' : '' ?>>
                                    <?= escaparSemilleroLinea($linea['nombre'] ?? 'Línea') ?>
                                    (ID <?= escaparSemilleroLinea($linea['id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary" <?= $puedeCrear ? '' : 'disabled' ?>>
                        Crear asociación
                    </button>
                    <a href="/asociaciones-semillero-linea" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div></div>
    </div></div>
    <?php
    renderizarPlantilla('Registrar asociación semillero-línea', (string) ob_get_clean());
}

/** Muestra los dos identificadores de la clave primaria como solo lectura. */
function mostrarDetalleSemilleroLinea(array $asociacion, array $semilleros = [], array $lineas = []): void
{
    $semilleroId = (int) ($asociacion['semillero'] ?? 0);
    $lineaId = (int) ($asociacion['linea_investigacion'] ?? 0);
    $nombreSemillero = 'Semillero';
    $nombreLinea = 'Línea de investigación';
    foreach ($semilleros as $semillero) {
        if (is_array($semillero) && (string) ($semillero['id'] ?? '') === (string) $semilleroId) {
            $nombreSemillero = (string) ($semillero['nombre'] ?? $nombreSemillero);
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
                <dt class="col-sm-4">Semillero</dt>
                <dd class="col-sm-8"><?= escaparSemilleroLinea($nombreSemillero) ?> (ID <?= escaparSemilleroLinea($semilleroId) ?>)</dd>
                <dt class="col-sm-4">Línea de investigación</dt>
                <dd class="col-sm-8"><?= escaparSemilleroLinea($nombreLinea) ?> (ID <?= escaparSemilleroLinea($lineaId) ?>)</dd>
            </dl>
            <div class="alert alert-info mt-3 mb-3">Esta asociación no tiene campos editables.
                Para cambiar la relación, registre otra y retire la anterior.</div>
            <a href="/asociaciones-semillero-linea" class="btn btn-outline-secondary">Volver al listado</a>
        </div></div>
    </div></div>
    <?php
    renderizarPlantilla('Detalle de asociación semillero-línea', (string) ob_get_clean());
}
