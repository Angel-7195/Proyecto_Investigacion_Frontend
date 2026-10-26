<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/../../cliente_api.php';

function escaparParticipaGrupo(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Muestra participaciones activas y los nombres de docente/grupo si la API los ofrece. */
function mostrarListaParticipacionesGrupo(array $errores = []): void
{
    $respuesta = listarParticipacionesGrupo();
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
    $nombresGrupos = [];
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
        $rGrupos = listarGruposInvestigacion();
        if ($rGrupos['correcta']) {
            foreach (($rGrupos['datos']['datos'] ?? []) as $grupo) {
                if (is_array($grupo) && isset($grupo['id'])) {
                    $nombresGrupos[(string) $grupo['id']] = (string) ($grupo['nombre'] ?? '');
                }
            }
        }
    }

    $mensaje = $_SESSION['mensaje_participa_grupo'] ?? '';
    unset($_SESSION['mensaje_participa_grupo']);
    ob_start();
    ?>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Participaciones en grupos</h1>
            <p class="text-muted mb-0">Administre la participación de docentes en grupos de investigación.</p>
        </div>
        <a href="/participaciones-grupo/nuevo" class="btn btn-primary">Registrar participación</a>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success" role="status"><?= escaparParticipaGrupo($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($errores !== []): ?>
        <div class="alert alert-danger" role="alert"><ul class="mb-0">
            <?php foreach ($errores as $error): ?>
                <li><?= escaparParticipaGrupo($error) ?></li>
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
                    <th scope="col">Grupo de investigación</th>
                    <th scope="col">Rol</th>
                    <th scope="col">Fecha de inicio</th>
                    <th scope="col">Fecha de finalización</th>
                    <th scope="col">Acciones</th>
                </tr></thead>
                <tbody>
                <?php foreach ($participaciones as $participacion): ?>
                    <?php if (!is_array($participacion)) { continue; } ?>
                    <?php
                        $cedula = (int) ($participacion['docente_cedula'] ?? 0);
                        $grupoId = (int) ($participacion['grupo_investigacion_id'] ?? 0);
                        $nombreDocente = $nombresDocentes[(string) $cedula] ?? 'Docente';
                        $nombreGrupo = $nombresGrupos[(string) $grupoId] ?? 'Grupo';
                    ?>
                    <tr>
                        <td><?= escaparParticipaGrupo($nombreDocente) ?> (<?= escaparParticipaGrupo($cedula) ?>)</td>
                        <td><?= escaparParticipaGrupo($nombreGrupo) ?> (ID <?= escaparParticipaGrupo($grupoId) ?>)</td>
                        <td><?= escaparParticipaGrupo($participacion['rol'] ?? '') ?></td>
                        <td><?= escaparParticipaGrupo($participacion['fecha_inicio'] ?? '') ?></td>
                        <td><?= escaparParticipaGrupo($participacion['fecha_fin'] ?? 'Sin fecha') ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-primary btn-sm"
                                   href="/participaciones-grupo/editar?cedula=<?= escaparParticipaGrupo($cedula) ?>&amp;grupo=<?= escaparParticipaGrupo($grupoId) ?>">Editar</a>
                                <form method="post" action="/participaciones-grupo/retirar"
                                      onsubmit="return confirm('¿Retirar esta participación?');">
                                    <input type="hidden" name="docente_cedula" value="<?= escaparParticipaGrupo($cedula) ?>">
                                    <input type="hidden" name="grupo_investigacion_id" value="<?= escaparParticipaGrupo($grupoId) ?>">
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
    renderizarPlantilla('Participaciones en grupos', (string) ob_get_clean());
}
