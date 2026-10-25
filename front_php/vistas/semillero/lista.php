<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/../../cliente_api.php';

function escaparSemillero(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Listado de semilleros activos consultados exclusivamente por HTTP. */
function mostrarListaSemilleros(array $errores = []): void
{
    $respuesta = listarSemilleros();
    $semilleros = [];
    if ($respuesta['correcta']) {
        $semilleros = $respuesta['datos']['datos'] ?? [];
        if (!is_array($semilleros)) {
            $semilleros = [];
            $errores[] = 'La API devolvió una lista de semilleros con formato inesperado.';
        }
    } elseif (!$respuesta['disponible'] || $respuesta['error'] !== '') {
        $errores[] = 'No fue posible consultar los semilleros. Revise la conexión con la API.';
    } else {
        $errores[] = 'No fue posible consultar los semilleros (HTTP ' . $respuesta['estado'] . ').';
    }

    $nombresGrupos = [];
    if ($semilleros !== []) {
        $respuestaGrupos = listarGruposInvestigacion();
        if ($respuestaGrupos['correcta']) {
            foreach (($respuestaGrupos['datos']['datos'] ?? []) as $grupo) {
                if (is_array($grupo) && isset($grupo['id'], $grupo['nombre'])) {
                    $nombresGrupos[(string) $grupo['id']] = (string) $grupo['nombre'];
                }
            }
        }
    }

    $mensaje = $_SESSION['mensaje_semillero'] ?? '';
    unset($_SESSION['mensaje_semillero']);

    ob_start();
    ?>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Semilleros</h1>
            <p class="text-muted mb-0">Administre los semilleros y sus grupos de investigación.</p>
        </div>
        <a href="/semilleros/nuevo" class="btn btn-primary">Registrar semillero</a>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success" role="status"><?= escaparSemillero($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($errores !== []): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?= escaparSemillero($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($respuesta['correcta'] && $semilleros === []): ?>
        <div class="alert alert-info">Todavía no hay semilleros activos.</div>
    <?php elseif ($semilleros !== []): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Fecha de fundación</th>
                        <th scope="col">Grupo de investigación</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($semilleros as $semillero): ?>
                        <?php if (!is_array($semillero)) { continue; } ?>
                        <?php
                            $id = (int) ($semillero['id'] ?? 0);
                            $idGrupo = (int) ($semillero['grupo_investigacion'] ?? 0);
                            $nombreGrupo = $nombresGrupos[(string) $idGrupo] ?? 'ID ' . $idGrupo;
                        ?>
                        <tr>
                            <td><?= escaparSemillero($id) ?></td>
                            <td><?= escaparSemillero($semillero['nombre'] ?? '') ?></td>
                            <td><?= escaparSemillero($semillero['fecha_fundacion'] ?? '') ?></td>
                            <td><?= escaparSemillero($nombreGrupo) ?> (ID <?= escaparSemillero($idGrupo) ?>)</td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-outline-primary btn-sm"
                                       href="/semilleros/editar?id=<?= escaparSemillero($id) ?>">Editar</a>
                                    <form method="post" action="/semilleros/retirar"
                                          onsubmit="return confirm('¿Retirar este semillero?');">
                                        <input type="hidden" name="id" value="<?= escaparSemillero($id) ?>">
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
    renderizarPlantilla('Semilleros', (string) ob_get_clean());
}
