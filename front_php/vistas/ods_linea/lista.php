<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/../../cliente_api.php';

function escaparOdsLinea(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Listado de asociaciones con nombres de línea y ODS cuando están disponibles. */
function mostrarListaOdsLinea(array $errores = []): void
{
    $respuesta = listarAsociacionesOdsLinea();
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

    $nombresLineas = [];
    $nombresOds = [];
    if ($asociaciones !== []) {
        $respuestaLineas = listarLineasInvestigacion();
        if ($respuestaLineas['correcta']) {
            foreach (($respuestaLineas['datos']['datos'] ?? []) as $linea) {
                if (is_array($linea) && isset($linea['id'])) {
                    $nombresLineas[(string) $linea['id']] = (string) ($linea['nombre'] ?? 'Línea');
                }
            }
        }
        $respuestaOds = listarObjetivosDesarrolloSostenibleOdsLinea();
        if ($respuestaOds['correcta']) {
            foreach (($respuestaOds['datos']['datos'] ?? []) as $ods) {
                if (is_array($ods) && isset($ods['id'])) {
                    $nombresOds[(string) $ods['id']] = (string) ($ods['nombre'] ?? 'ODS');
                }
            }
        }
    }

    $mensaje = $_SESSION['mensaje_ods_linea'] ?? '';
    unset($_SESSION['mensaje_ods_linea']);
    ob_start();
    ?>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">ODS por línea de investigación</h1>
            <p class="text-muted mb-0">Administre las relaciones entre líneas de investigación y Objetivos de Desarrollo Sostenible.</p>
        </div>
        <a class="btn btn-primary" href="/asociaciones-ods-linea/nuevo">Registrar asociación</a>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success" role="status"><?= escaparOdsLinea($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($errores !== []): ?>
        <div class="alert alert-danger" role="alert"><ul class="mb-0">
            <?php foreach ($errores as $error): ?>
                <li><?= escaparOdsLinea($error) ?></li>
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
                    <th scope="col">ODS</th>
                    <th scope="col">Acciones</th>
                </tr></thead>
                <tbody>
                <?php foreach ($asociaciones as $asociacion): ?>
                    <?php if (!is_array($asociacion)) { continue; } ?>
                    <?php
                        $lineaId = (int) ($asociacion['linea_investigacion'] ?? 0);
                        $odsId = (int) ($asociacion['ods'] ?? 0);
                        $nombreLinea = $nombresLineas[(string) $lineaId] ?? 'Línea de investigación';
                        $nombreOds = $nombresOds[(string) $odsId] ?? 'Objetivo de Desarrollo Sostenible';
                        $urlVer = '/asociaciones-ods-linea/ver?' . http_build_query([
                            'linea_investigacion' => $lineaId,
                            'ods' => $odsId,
                        ]);
                    ?>
                    <tr>
                        <td><?= escaparOdsLinea($nombreLinea) ?> (ID <?= escaparOdsLinea($lineaId) ?>)</td>
                        <td>ODS <?= escaparOdsLinea($odsId) ?> — <?= escaparOdsLinea($nombreOds) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-primary btn-sm" href="<?= escaparOdsLinea($urlVer) ?>">Ver detalle</a>
                                <form method="post" action="/asociaciones-ods-linea/retirar"
                                      onsubmit="return confirm('¿Retirar esta asociación?');">
                                    <input type="hidden" name="linea_investigacion" value="<?= escaparOdsLinea($lineaId) ?>">
                                    <input type="hidden" name="ods" value="<?= escaparOdsLinea($odsId) ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Retirar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted small">Las dos claves de esta asociación no se pueden editar. Para cambiar la relación, registre otra y retire la anterior.</p>
    <?php endif; ?>
    <?php
    renderizarPlantilla('ODS por línea de investigación', (string) ob_get_clean());
}
