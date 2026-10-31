<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/../../cliente_api.php';

function escaparAaLinea(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Muestra la lista de relaciones activas con nombres legibles. */
function mostrarListaAaLinea(array $errores = []): void
{
    $respuesta = listarAsociacionesAaLinea();
    $asociaciones = [];
    if ($respuesta['correcta']) {
        $asociaciones = $respuesta['datos']['datos'] ?? [];
        if (!is_array($asociaciones)) {
            $asociaciones = [];
            $errores[] = 'La API devolvió un listado con formato inesperado.';
        }
    } elseif (!$respuesta['disponible'] || $respuesta['error'] !== '') {
        $errores[] = 'No se pudieron consultar las asociaciones. Verifique la conexión con la API.';
    } else {
        $errores[] = 'La API rechazó el listado (HTTP ' . $respuesta['estado'] . ').';
    }

    $nombresAreas = [];
    $nombresLineas = [];
    if ($asociaciones !== []) {
        $respuestaAreas = listarAreasAplicacionAaLinea();
        if ($respuestaAreas['correcta']) {
            foreach (($respuestaAreas['datos']['datos'] ?? []) as $area) {
                if (is_array($area) && isset($area['id'])) {
                    $nombresAreas[(string) $area['id']] = (string) ($area['nombre'] ?? 'Área de aplicación');
                }
            }
        }
        $respuestaLineas = listarLineasInvestigacion();
        if ($respuestaLineas['correcta']) {
            foreach (($respuestaLineas['datos']['datos'] ?? []) as $linea) {
                if (is_array($linea) && isset($linea['id'])) {
                    $nombresLineas[(string) $linea['id']] = (string) ($linea['nombre'] ?? 'Línea');
                }
            }
        }
    }

    $mensaje = $_SESSION['mensaje_aa_linea'] ?? '';
    unset($_SESSION['mensaje_aa_linea']);
    ob_start();
    ?>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Áreas de aplicación por línea</h1>
            <p class="text-muted mb-0">Administre las relaciones entre áreas de aplicación y líneas de investigación.</p>
        </div>
        <a class="btn btn-primary" href="/asociaciones-aa-linea/nuevo">Registrar asociación</a>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success" role="status"><?= escaparAaLinea($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($errores !== []): ?>
        <div class="alert alert-danger" role="alert"><ul class="mb-0">
            <?php foreach ($errores as $error): ?>
                <li><?= escaparAaLinea($error) ?></li>
            <?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <?php if ($respuesta['correcta'] && $asociaciones === []): ?>
        <div class="alert alert-info">Todavía no hay asociaciones activas.</div>
    <?php elseif ($asociaciones !== []): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark"><tr>
                    <th scope="col">Área de aplicación</th>
                    <th scope="col">Línea de investigación</th>
                    <th scope="col">Acciones</th>
                </tr></thead>
                <tbody>
                <?php foreach ($asociaciones as $asociacion): ?>
                    <?php if (!is_array($asociacion)) { continue; } ?>
                    <?php
                        $areaId = (int) ($asociacion['area_aplicacion'] ?? 0);
                        $lineaId = (int) ($asociacion['linea_investigacion'] ?? 0);
                        $nombreArea = $nombresAreas[(string) $areaId] ?? 'Área de aplicación';
                        $nombreLinea = $nombresLineas[(string) $lineaId] ?? 'Línea de investigación';
                        $urlVer = '/asociaciones-aa-linea/ver?' . http_build_query([
                            'area_aplicacion' => $areaId,
                            'linea_investigacion' => $lineaId,
                        ]);
                    ?>
                    <tr>
                        <td><?= escaparAaLinea($nombreArea) ?> (ID <?= escaparAaLinea($areaId) ?>)</td>
                        <td><?= escaparAaLinea($nombreLinea) ?> (ID <?= escaparAaLinea($lineaId) ?>)</td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-primary btn-sm" href="<?= escaparAaLinea($urlVer) ?>">Ver detalle</a>
                                <form method="post" action="/asociaciones-aa-linea/retirar"
                                      onsubmit="return confirm('¿Retirar esta asociación?');">
                                    <input type="hidden" name="area_aplicacion" value="<?= escaparAaLinea($areaId) ?>">
                                    <input type="hidden" name="linea_investigacion" value="<?= escaparAaLinea($lineaId) ?>">
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
    renderizarPlantilla('Áreas de aplicación por línea', (string) ob_get_clean());
}
