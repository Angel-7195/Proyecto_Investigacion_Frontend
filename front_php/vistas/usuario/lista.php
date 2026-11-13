<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';
require_once __DIR__ . '/../../cliente_api.php';

/** Listado administrativo de usuarios activos; nunca muestra contraseñas. */
function mostrarListaUsuarios(): void
{
    $respuesta = listarUsuarios();
    $estado = (int) $respuesta['estado'];
    $registros = $respuesta['datos']['datos'] ?? [];
    if (!is_array($registros)) {
        $registros = [];
    }

    $mensaje = $_SESSION['mensaje_usuarios'] ?? '';
    unset($_SESSION['mensaje_usuarios']);

    ob_start();
    ?>
    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
        <div>
            <h1 class="h2">Administración de usuarios</h1>
            <p class="text-muted mb-0">Consulta y administra las cuentas activas del sistema.</p>
        </div>
        <a href="/usuarios/nuevo" class="btn btn-primary">Registrar usuario</a>
    </div>

    <?php if (is_string($mensaje) && $mensaje !== ''): ?>
        <div class="alert alert-success" role="status">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!$respuesta['disponible']): ?>
        <div class="alert alert-warning" role="alert">La API no está disponible.</div>
    <?php elseif ($estado === 401 || $estado === 403): ?>
        <div class="alert alert-danger" role="alert">Tu sesión no tiene autorización vigente para consultar usuarios.</div>
    <?php elseif ($estado === 204 || ($estado === 200 && $registros === [])): ?>
        <div class="alert alert-info">Todavía no hay usuarios activos registrados.</div>
    <?php elseif (!$respuesta['correcta']): ?>
        <div class="alert alert-danger" role="alert">No fue posible consultar los usuarios.</div>
    <?php else: ?>
        <p class="text-muted">Usuarios activos: <?= count($registros) ?></p>
        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Correo electrónico</th>
                        <th>Nombre completo</th>
                        <th>Fecha de creación</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $registro): ?>
                        <?php if (!is_array($registro)) { continue; } ?>
                        <tr>
                            <td><?= (int) ($registro['id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($registro['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($registro['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($registro['nombre_completo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($registro['fecha_creacion'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="/usuarios/editar?id=<?= (int) ($registro['id'] ?? 0) ?>">Editar</a>
                                    <a class="btn btn-sm btn-outline-danger"
                                       href="/usuarios/editar?id=<?= (int) ($registro['id'] ?? 0) ?>&amp;seccion=eliminar">Desactivar</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php
    renderizarPlantilla('Administración de usuarios', (string) ob_get_clean());
}
