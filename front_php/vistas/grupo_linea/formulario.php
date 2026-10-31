<?php

declare(strict_types=1);

require_once __DIR__ . '/lista.php';

/** Formulario de registro con listas de las dos entidades relacionadas. */
function mostrarFormularioGrupoLinea(
    array $valores = [],
    array $errores = [],
    array $grupos = [],
    array $lineas = [],
    bool $gruposDisponibles = true,
    bool $lineasDisponibles = true
): void {
    $grupoElegido = (string) ($valores['grupo_investigacion'] ?? '');
    $lineaElegida = (string) ($valores['linea_investigacion'] ?? '');
    $puedeCrear = $gruposDisponibles && $lineasDisponibles
        && $grupos !== [] && $lineas !== [];

    // Presentación por nombre del catálogo de grupos de investigación.
    $gruposOrdenados = array_values(array_filter($grupos, 'is_array'));
    usort($gruposOrdenados, static function (array $a, array $b): int {
        return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
    });

    ob_start();
    ?>
    <div class="row justify-content-center"><div class="col-lg-9">
        <div class="mb-4">
            <h1 class="h2">Registrar grupo en línea de investigación</h1>
            <p class="text-muted">Seleccione un grupo de investigación y una línea de investigación activos.</p>
        </div>
        <?php if ($errores !== []): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Revise la información:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errores as $error): ?>
                        <li><?= escaparGrupoLinea($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!$gruposDisponibles || !$lineasDisponibles): ?>
            <div class="alert alert-warning">No fue posible consultar grupos o líneas activas. Verifique la API.</div>
        <?php elseif ($grupos === [] || $lineas === []): ?>
            <div class="alert alert-info">Para registrar una asociación necesita al menos
                <a href="/grupos-investigacion/nuevo">un grupo de investigación</a> y
                <a href="/lineas-investigacion/nuevo">una línea de investigación</a> activas.</div>
        <?php endif; ?>

        <div class="card shadow-sm"><div class="card-body p-4">
            <form method="post" action="/asociaciones-grupo-linea/crear">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="grupo-linea-grupo-id" class="form-label">Grupo de investigación *</label>
                        <select id="grupo-linea-grupo-id" name="grupo_investigacion" class="form-select" required
                            <?= $gruposDisponibles ? '' : 'disabled' ?>>
                            <option value="">Seleccione un grupo activo</option>
                            <?php foreach ($gruposOrdenados as $grupo): ?>
                                <?php if (!isset($grupo['id'])) { continue; } ?>
                                <option value="<?= escaparGrupoLinea($grupo['id']) ?>"
                                    <?= (string) $grupo['id'] === $grupoElegido ? 'selected' : '' ?>>
                                    <?= escaparGrupoLinea($grupo['nombre'] ?? 'Grupo de investigación') ?>
                                    (ID <?= escaparGrupoLinea($grupo['id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="grupo-linea-linea-id" class="form-label">Línea de investigación *</label>
                        <select id="grupo-linea-linea-id" name="linea_investigacion" class="form-select" required
                            <?= $lineasDisponibles ? '' : 'disabled' ?>>
                            <option value="">Seleccione una línea activa</option>
                            <?php foreach ($lineas as $linea): ?>
                                <?php if (!is_array($linea) || !isset($linea['id'])) { continue; } ?>
                                <option value="<?= escaparGrupoLinea($linea['id']) ?>"
                                    <?= (string) $linea['id'] === $lineaElegida ? 'selected' : '' ?>>
                                    <?= escaparGrupoLinea($linea['nombre'] ?? 'Línea') ?>
                                    (ID <?= escaparGrupoLinea($linea['id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary" <?= $puedeCrear ? '' : 'disabled' ?>>
                        Crear asociación
                    </button>
                    <a href="/asociaciones-grupo-linea" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div></div>
    </div></div>
    <?php
    renderizarPlantilla('Registrar asociación grupo-línea', (string) ob_get_clean());
}

/** Muestra los dos identificadores de la clave primaria como solo lectura. */
function mostrarDetalleGrupoLinea(array $asociacion, array $grupos = [], array $lineas = []): void
{
    $grupoId = (int) ($asociacion['grupo_investigacion'] ?? 0);
    $lineaId = (int) ($asociacion['linea_investigacion'] ?? 0);
    $nombreGrupo = 'Grupo de investigación';
    $nombreLinea = 'Línea de investigación';
    foreach ($grupos as $grupo) {
        if (is_array($grupo) && (string) ($grupo['id'] ?? '') === (string) $grupoId) {
            $nombreGrupo = (string) ($grupo['nombre'] ?? $nombreGrupo);
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
                <dt class="col-sm-4">Grupo de investigación</dt>
                <dd class="col-sm-8"><?= escaparGrupoLinea($nombreGrupo) ?> (ID <?= escaparGrupoLinea($grupoId) ?>)</dd>
                <dt class="col-sm-4">Línea de investigación</dt>
                <dd class="col-sm-8"><?= escaparGrupoLinea($nombreLinea) ?> (ID <?= escaparGrupoLinea($lineaId) ?>)</dd>
            </dl>
            <div class="alert alert-info mt-3 mb-3">Esta asociación no tiene campos editables.
                Para cambiar la relación, registre otra y retire la anterior.</div>
            <a href="/asociaciones-grupo-linea" class="btn btn-outline-secondary">Volver al listado</a>
        </div></div>
    </div></div>
    <?php
    renderizarPlantilla('Detalle de asociación grupo-línea', (string) ob_get_clean());
}
