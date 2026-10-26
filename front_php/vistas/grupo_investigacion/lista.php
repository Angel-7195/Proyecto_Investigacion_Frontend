<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/../../cliente_api.php';

function escaparGrupoInvestigacion(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Lista únicamente grupos activos; los datos llegan por HTTP desde la API.
 * El frontend nunca consulta MariaDB directamente.
 */
function mostrarListaGruposInvestigacion(array $errores = []): void
{
    $respuesta = listarGruposInvestigacion();
    $grupos = [];
    $codigo = $respuesta['estado'];

    if ($respuesta['correcta']) {
        $grupos = $respuesta['datos']['datos'] ?? [];
        if (!is_array($grupos)) {
            $grupos = [];
            $errores[] = 'La API devolvió una lista con formato inesperado.';
        }
    } elseif (!$respuesta['disponible'] || $respuesta['error'] !== '') {
        $errores[] = 'No fue posible obtener los grupos. Revise la conexión con la API.';
    } else {
        $errores[] = 'No fue posible consultar los grupos de investigación (HTTP ' . $codigo . ').';
    }

    // El nombre de la universidad se usa solo para mostrarlo en la tabla.
    $nombresUniversidades = [];
    if ($grupos !== []) {
        $universidades = listarUniversidades();
        if ($universidades['correcta']) {
            foreach (($universidades['datos']['datos'] ?? []) as $universidad) {
                if (is_array($universidad) && isset($universidad['id'], $universidad['nombre'])) {
                    $nombresUniversidades[(string) $universidad['id']] = (string) $universidad['nombre'];
                }
            }
        }
    }

    $mensaje = $_SESSION['mensaje_grupo_investigacion'] ?? '';
    unset($_SESSION['mensaje_grupo_investigacion']);

    ob_start();
    ?>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Grupos de investigación</h1>
            <p class="text-muted mb-0">Administre los grupos y sus universidades relacionadas.</p>
        </div>
        <a href="/grupos-investigacion/nuevo" class="btn btn-primary">Registrar grupo</a>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success" role="status"><?= escaparGrupoInvestigacion($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($errores !== []): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?= escaparGrupoInvestigacion($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($respuesta['correcta'] && $grupos === []): ?>
        <div class="alert alert-info">Todavía no hay grupos de investigación activos.</div>
    <?php elseif ($grupos !== []): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Categoría</th>
                        <th scope="col">Universidad</th>
                        <th scope="col">Interno</th>
                        <th scope="col">Ámbito</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grupos as $grupo): ?>
                        <?php if (!is_array($grupo)) { continue; } ?>
                        <?php
                            $id = (int) ($grupo['id'] ?? 0);
                            $universidadId = $grupo['universidsad'] ?? null;
                            $universidadTexto = $universidadId === null
                                ? 'Sin universidad'
                                : ($nombresUniversidades[(string) $universidadId] ?? 'ID ' . $universidadId);
                        ?>
                        <tr>
                            <td><?= escaparGrupoInvestigacion($id) ?></td>
                            <td><?= escaparGrupoInvestigacion($grupo['nombre'] ?? '') ?></td>
                            <td><?= escaparGrupoInvestigacion($grupo['categoria'] ?? '—') ?></td>
                            <td><?= escaparGrupoInvestigacion($universidadTexto) ?></td>
                            <td><?= (int) ($grupo['interno'] ?? 0) === 1 ? 'Sí' : 'No' ?></td>
                            <td><?= escaparGrupoInvestigacion($grupo['ambito'] ?? '') ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-outline-primary btn-sm"
                                       href="/grupos-investigacion/editar?id=<?= escaparGrupoInvestigacion($id) ?>">Editar</a>
                                    <form method="post" action="/grupos-investigacion/retirar"
                                          onsubmit="return confirm('¿Retirar este grupo de investigación?');">
                                        <input type="hidden" name="id" value="<?= escaparGrupoInvestigacion($id) ?>">
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
    renderizarPlantilla('Grupos de investigación', (string) ob_get_clean());
}
