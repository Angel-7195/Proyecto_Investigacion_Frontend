<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/../../cliente_api.php';

function escaparSemilleroLinea(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Muestra la lista de relaciones activas con nombres legibles. */
function mostrarListaSemilleroLinea(array $errores = []): void
{
    $respuesta = listarAsociacionesSemilleroLinea();
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

    $nombresSemilleros = [];
    $nombresLineas = [];
    if ($asociaciones !== []) {
        $respuestaSemilleros = listarSemilleros();
        if ($respuestaSemilleros['correcta']) {
            foreach (($respuestaSemilleros['datos']['datos'] ?? []) as $semillero) {
                if (is_array($semillero) && isset($semillero['id'])) {
                    $nombresSemilleros[(string) $semillero['id']] = (string) ($semillero['nombre'] ?? 'Semillero');
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

    $mensaje = $_SESSION['mensaje_semillero_linea'] ?? '';
    unset($_SESSION['mensaje_semillero_linea']);
    ob_start();
    ?>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Semilleros por línea</h1>
            <p class="text-muted mb-0">Administre las relaciones entre semilleros y líneas de investigación.</p>
        </div>
        <a class="btn btn-primary" href="/asociaciones-semillero-linea/nuevo">Registrar asociación</a>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success" role="status"><?= escaparSemilleroLinea($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($errores !== []): ?>
        <div class="alert alert-danger" role="alert"><ul class="mb-0">
            <?php foreach ($errores as $error): ?>
                <li><?= escaparSemilleroLinea($error) ?></li>
            <?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <?php if ($respuesta['correcta'] && $asociaciones === []): ?>
        <div class="alert alert-info">Todavía no hay asociaciones activas.</div>
    <?php elseif ($asociaciones !== []): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark"><tr>
                    <th scope="col">Semillero</th>
                    <th scope="col">Línea de investigación</th>
                    <th scope="col">Acciones</th>
                </tr></thead>
                <tbody>
                <?php foreach ($asociaciones as $asociacion): ?>
                    <?php if (!is_array($asociacion)) { continue; } ?>
                    <?php
                        $semilleroId = (int) ($asociacion['semillero'] ?? 0);
                        $lineaId = (int) ($asociacion['linea_investigacion'] ?? 0);
                        $nombreSemillero = $nombresSemilleros[(string) $semilleroId] ?? 'Semillero';
                        $nombreLinea = $nombresLineas[(string) $lineaId] ?? 'Línea de investigación';
                        $urlVer = '/asociaciones-semillero-linea/ver?' . http_build_query([
                            'semillero' => $semilleroId,
                            'linea_investigacion' => $lineaId,
                        ]);
                    ?>
                    <tr>
                        <td><?= escaparSemilleroLinea($nombreSemillero) ?> (ID <?= escaparSemilleroLinea($semilleroId) ?>)</td>
                        <td><?= escaparSemilleroLinea($nombreLinea) ?> (ID <?= escaparSemilleroLinea($lineaId) ?>)</td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-primary btn-sm" href="<?= escaparSemilleroLinea($urlVer) ?>">Ver detalle</a>
                                <form method="post" action="/asociaciones-semillero-linea/retirar"
                                      onsubmit="return confirm('¿Retirar esta asociación?');">
                                    <input type="hidden" name="semillero" value="<?= escaparSemilleroLinea($semilleroId) ?>">
                                    <input type="hidden" name="linea_investigacion" value="<?= escaparSemilleroLinea($lineaId) ?>">
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
    renderizarPlantilla('Semilleros por línea', (string) ob_get_clean());
}
