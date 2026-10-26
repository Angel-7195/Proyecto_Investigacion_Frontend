<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/../../cliente_api.php';

function escaparDocente(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Listado de docentes activos, obtenido exclusivamente mediante API HTTP. */
function mostrarListaDocentes(array $errores = []): void
{
    $respuesta = listarDocentes();
    $docentes = [];
    if ($respuesta['correcta']) {
        $docentes = $respuesta['datos']['datos'] ?? [];
        if (!is_array($docentes)) {
            $docentes = [];
            $errores[] = 'La API devolvió una lista de docentes con formato inesperado.';
        }
    } elseif (!$respuesta['disponible'] || $respuesta['error'] !== '') {
        $errores[] = 'No fue posible consultar los docentes. Revise la conexión con la API.';
    } else {
        $errores[] = 'No fue posible consultar los docentes (HTTP ' . $respuesta['estado'] . ').';
    }

    $nombresLineas = [];
    if ($docentes !== []) {
        $lineas = listarLineasInvestigacion();
        if ($lineas['correcta']) {
            foreach (($lineas['datos']['datos'] ?? []) as $linea) {
                if (is_array($linea) && isset($linea['id'], $linea['nombre'])) {
                    $nombresLineas[(string) $linea['id']] = (string) $linea['nombre'];
                }
            }
        }
    }

    $mensaje = $_SESSION['mensaje_docente'] ?? '';
    unset($_SESSION['mensaje_docente']);

    ob_start();
    ?>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Docentes</h1>
            <p class="text-muted mb-0">Administre los docentes y sus líneas de investigación principales.</p>
        </div>
        <a href="/docentes/nuevo" class="btn btn-primary">Registrar docente</a>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success" role="status"><?= escaparDocente($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($errores !== []): ?>
        <div class="alert alert-danger" role="alert"><ul class="mb-0">
            <?php foreach ($errores as $error): ?>
                <li><?= escaparDocente($error) ?></li>
            <?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <?php if ($respuesta['correcta'] && $docentes === []): ?>
        <div class="alert alert-info">Todavía no hay docentes activos.</div>
    <?php elseif ($docentes !== []): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark"><tr>
                    <th scope="col">Cédula</th>
                    <th scope="col">Nombres</th>
                    <th scope="col">Apellidos</th>
                    <th scope="col">Correo</th>
                    <th scope="col">Cargo</th>
                    <th scope="col">Línea principal</th>
                    <th scope="col">Acciones</th>
                </tr></thead>
                <tbody>
                <?php foreach ($docentes as $docente): ?>
                    <?php if (!is_array($docente)) { continue; } ?>
                    <?php
                        $cedula = (int) ($docente['cedula'] ?? 0);
                        $idLinea = $docente['linea_investigacion_principal'] ?? null;
                        $lineaTexto = $idLinea === null ? 'Sin línea asignada'
                            : ($nombresLineas[(string) $idLinea] ?? 'Línea ID ' . $idLinea);
                    ?>
                    <tr>
                        <td><?= escaparDocente($cedula) ?></td>
                        <td><?= escaparDocente($docente['nombres'] ?? '') ?></td>
                        <td><?= escaparDocente($docente['apellidos'] ?? '') ?></td>
                        <td><?= escaparDocente($docente['correo'] ?? '') ?></td>
                        <td><?= escaparDocente($docente['cargo'] ?? '') ?></td>
                        <td><?= escaparDocente($lineaTexto) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-primary btn-sm"
                                   href="/docentes/editar?cedula=<?= escaparDocente($cedula) ?>">Editar</a>
                                <form method="post" action="/docentes/retirar"
                                      onsubmit="return confirm('¿Retirar este docente?');">
                                    <input type="hidden" name="cedula" value="<?= escaparDocente($cedula) ?>">
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
    renderizarPlantilla('Docentes', (string) ob_get_clean());
}
