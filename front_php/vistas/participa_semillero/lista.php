<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/../../cliente_api.php';

function escaparParticipaSemillero(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Muestra participaciones activas y los nombres de docente/semillero si la API los ofrece. */
function mostrarListaParticipacionesSemillero(array $errores = []): void
{
    $respuesta = listarParticipacionesSemillero();
    $participaciones = [];
    if ($respuesta['correcta']) {
        $participaciones = $respuesta['datos']['datos'] ?? [];
        if (!is_array($participaciones)) {
            $participaciones = [];
            $errores[] = 'La API devolvió un listado con formato inesperado.';
        }
    } elseif (!$respuesta['disponible'] || $respuesta['error'] !== '') {
        $errores[] = 'No se pudo consultar las participaciones. Revise la conexión con la API.';
    } else {
        $errores[] = 'No se pudo consultar las participaciones (HTTP ' . $respuesta['estado'] . ').';
    }

    $nombresDocentes = [];
    $nombresSemilleros = [];
    if ($participaciones !== []) {
        $rDocentes = listarDocentes();
        if ($rDocentes['correcta']) {
            foreach (($rDocentes['datos']['datos'] ?? []) as $docente) {
                if (is_array($docente) && isset($docente['cedula'])) {
                    $nombresDocentes[(string) $docente['cedula']] = trim(
                        (string) ($docente['nombres'] ?? '') . ' ' . (string) ($docente['apellidos'] ?? '')
                    );
                }
            }
        }
        $rSemilleros = listarSemilleros();
        if ($rSemilleros['correcta']) {
            foreach (($rSemilleros['datos']['datos'] ?? []) as $semillero) {
                if (is_array($semillero) && isset($semillero['id'])) {
                    $nombresSemilleros[(string) $semillero['id']] = (string) ($semillero['nombre'] ?? '');
                }
            }
        }
    }

    $mensaje = $_SESSION['mensaje_participa_semillero'] ?? '';
    unset($_SESSION['mensaje_participa_semillero']);
    ob_start();
    ?>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Participaciones en semilleros</h1>
            <p class="text-muted mb-0">Administre la participación de docentes en semilleros de investigación.</p>
        </div>
        <a href="/participaciones-semillero/nuevo" class="btn btn-primary">Registrar participación</a>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success" role="status"><?= escaparParticipaSemillero($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($errores !== []): ?>
        <div class="alert alert-danger" role="alert"><ul class="mb-0">
            <?php foreach ($errores as $error): ?>
                <li><?= escaparParticipaSemillero($error) ?></li>
            <?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <?php if ($respuesta['correcta'] && $participaciones === []): ?>
        <div class="alert alert-info">Todavía no hay participaciones activas.</div>
    <?php elseif ($participaciones !== []): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark"><tr>
                    <th scope="col">Docente</th>
                    <th scope="col">Semillero de investigación</th>
                    <th scope="col">Rol</th>
                    <th scope="col">Fecha de inicio</th>
                    <th scope="col">Fecha de finalización</th>
                    <th scope="col">Acciones</th>
                </tr></thead>
                <tbody>
                <?php foreach ($participaciones as $participacion): ?>
                    <?php if (!is_array($participacion)) { continue; } ?>
                    <?php
                        $cedula = (int) ($participacion['docente'] ?? 0);
                        $semilleroId = (int) ($participacion['semillero'] ?? 0);
                        $nombreDocente = $nombresDocentes[(string) $cedula] ?? 'Docente';
                        $nombreSemillero = $nombresSemilleros[(string) $semilleroId] ?? 'Semillero';
                    ?>
                    <tr>
                        <td><?= escaparParticipaSemillero($nombreDocente) ?> (<?= escaparParticipaSemillero($cedula) ?>)</td>
                        <td><?= escaparParticipaSemillero($nombreSemillero) ?> (ID <?= escaparParticipaSemillero($semilleroId) ?>)</td>
                        <td><?= escaparParticipaSemillero($participacion['rol'] ?? '') ?></td>
                        <td><?= escaparParticipaSemillero($participacion['fecha_inicio'] ?? '') ?></td>
                        <td><?= escaparParticipaSemillero($participacion['fecha_fin'] ?? 'Sin fecha') ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-primary btn-sm"
                                   href="/participaciones-semillero/editar?cedula=<?= escaparParticipaSemillero($cedula) ?>&amp;semillero=<?= escaparParticipaSemillero($semilleroId) ?>">Editar</a>
                                <form method="post" action="/participaciones-semillero/retirar"
                                      onsubmit="return confirm('¿Retirar esta participación?');">
                                    <input type="hidden" name="docente" value="<?= escaparParticipaSemillero($cedula) ?>">
                                    <input type="hidden" name="semillero" value="<?= escaparParticipaSemillero($semilleroId) ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Retirar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php
    renderizarPlantilla('Participaciones en semilleros', (string) ob_get_clean());
}
