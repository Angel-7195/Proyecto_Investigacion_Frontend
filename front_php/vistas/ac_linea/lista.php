<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/../../cliente_api.php';

function escaparAcLinea(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Lista asociaciones activas, con los nombres de ambos catálogos cuando existan. */
function mostrarListaAcLinea(array $errores = []): void
{
    $respuesta = listarAsociacionesAcLinea();
    $asociaciones = [];
    if ($respuesta['correcta']) {
        $asociaciones = $respuesta['datos']['datos'] ?? [];
        if (!is_array($asociaciones)) {
            $asociaciones = [];
            $errores[] = 'La API devolvió un listado con formato inesperado.';
        }
    } elseif (!$respuesta['disponible'] || $respuesta['error'] !== '') {
        $errores[] = 'No se pudo consultar las asociaciones. Verifique la conexión con la API.';
    } else {
        $errores[] = 'La API rechazó el listado (HTTP ' . $respuesta['estado'] . ').';
    }

    $nombresLineas = [];
    $nombresAreas = [];
    if ($asociaciones !== []) {
        $respuestaLineas = listarLineasInvestigacion();
        if ($respuestaLineas['correcta']) {
            foreach (($respuestaLineas['datos']['datos'] ?? []) as $linea) {
                if (is_array($linea) && isset($linea['id'])) {
                    $nombresLineas[(string) $linea['id']] = (string) ($linea['nombre'] ?? '');
                }
            }
        }
        $respuestaAreas = listarAreasConocimientoAcLinea();
        if ($respuestaAreas['correcta']) {
            foreach (($respuestaAreas['datos']['datos'] ?? []) as $area) {
                if (is_array($area) && isset($area['id'])) {
                    $nombresAreas[(string) $area['id']] = trim(
                        (string) ($area['gran_area'] ?? '') . ' · ' .
                        (string) ($area['area'] ?? '') . ' · ' .
                        (string) ($area['disciplina'] ?? '')
                    , ' ·');
                }
            }
        }
    }

    $mensaje = $_SESSION['mensaje_ac_linea'] ?? '';
    unset($_SESSION['mensaje_ac_linea']);
    ob_start();
    ?>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Áreas de conocimiento por línea</h1>
            <p class="text-muted mb-0">Administre las relaciones entre líneas de investigación y áreas de conocimiento.</p>
        </div>
        <a class="btn btn-primary" href="/asociaciones-ac-linea/nuevo">Registrar asociación</a>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success" role="status"><?= escaparAcLinea($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($errores !== []): ?>
        <div class="alert alert-danger" role="alert"><ul class="mb-0">
            <?php foreach ($errores as $error): ?>
                <li><?= escaparAcLinea($error) ?></li>
            <?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <?php if ($respuesta['correcta'] && $asociaciones === []): ?>
        <div class="alert alert-info">Todavía no hay asociaciones activas.</div>
    <?php elseif ($asociaciones !== []): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark"><tr>
                    <th scope="col">Línea de investigación</th>
                    <th scope="col">Área de conocimiento</th>
                    <th scope="col">Acciones</th>
                </tr></thead>
                <tbody>
                <?php foreach ($asociaciones as $asociacion): ?>
                    <?php if (!is_array($asociacion)) { continue; } ?>
                    <?php
                        $lineaId = (int) ($asociacion['linea_investigacion'] ?? 0);
                        $areaId = (string) ($asociacion['area_conocimiento'] ?? '');
                        $nombreLinea = $nombresLineas[(string) $lineaId] ?? 'Línea de investigación';
                        $nombreArea = $nombresAreas[$areaId] ?? 'Área de conocimiento';
                        $urlVer = '/asociaciones-ac-linea/ver?' . http_build_query([
                            'linea_investigacion' => $lineaId,
                            'area_conocimiento' => $areaId,
                        ]);
                    ?>
                    <tr>
                        <td><?= escaparAcLinea($nombreLinea) ?> (ID <?= escaparAcLinea($lineaId) ?>)</td>
                        <td><?= escaparAcLinea($nombreArea) ?> (<?= escaparAcLinea($areaId) ?>)</td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-primary btn-sm" href="<?= escaparAcLinea($urlVer) ?>">Ver detalle</a>
                                <form method="post" action="/asociaciones-ac-linea/retirar"
                                      onsubmit="return confirm('¿Retirar esta asociación?');">
                                    <input type="hidden" name="linea_investigacion" value="<?= escaparAcLinea($lineaId) ?>">
                                    <input type="hidden" name="area_conocimiento" value="<?= escaparAcLinea($areaId) ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Retirar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted small">Las claves de esta asociación no se pueden editar. Para cambiar la relación, registre otra y retire la anterior.</p>
    <?php endif; ?>
    <?php
    renderizarPlantilla('Áreas de conocimiento por línea', (string) ob_get_clean());
}
