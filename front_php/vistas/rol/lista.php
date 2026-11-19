<?php

declare(strict_types=1);

function escaparRol(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function mostrarListaRoles(array $roles, string $mensaje = ''): void
{
    ob_start();
    ?>
    <h1>Catálogo de roles</h1>
    <?php if ($mensaje !== ''): ?>
        <p class="alert alert-success" role="status"><?= escaparRol($mensaje) ?></p>
    <?php endif; ?>
    <a class="btn btn-primary mb-3" href="/roles/nuevo">Nuevo rol</a>
    <?php if ($roles === []): ?>
        <p>No hay roles activos.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($roles as $registro): ?>
                    <?php $id = (int) $registro['id']; ?>
                    <tr>
                        <td><?= $id ?></td>
                        <td><?= escaparRol($registro['nombre']) ?></td>
                        <td><?= escaparRol($registro['descripcion'] ?? '') ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="/roles/editar?id=<?= $id ?>">Editar</a>
                            <?php if ($registro['nombre'] !== 'admin'): ?>
                                <a class="btn btn-sm btn-outline-danger"
                                   href="/roles/confirmar-retiro?id=<?= $id ?>">Retirar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php
    renderizarPlantilla('Catálogo de roles', ob_get_clean());
}

function mostrarErrorRol(string $mensaje): void
{
    renderizarPlantilla('Catálogo de roles',
        '<p class="alert alert-warning" role="alert">' . escaparRol($mensaje) . '</p>'
        . '<a href="/roles">Volver al catálogo</a>');
}

function mostrarErrorApiRol(array $respuesta): void
{
    $estado = $respuesta['estado'];
    if (in_array($estado, [401, 403, 404, 409, 422], true)) {
        http_response_code($estado);
    } else {
        http_response_code($respuesta['disponible'] ? 502 : 503);
    }

    $mensaje = match ($estado) {
        401 => 'Tu sesión venció. Vuelve a iniciar sesión.',
        403 => 'Tu sesión no tiene permiso para administrar roles.',
        404 => 'El rol no existe o fue retirado.',
        409 => $respuesta['datos']['mensaje'] ?? 'La operación generó un conflicto.',
        422 => 'Los datos enviados no son válidos.',
        0 => 'No fue posible conectar con la API.',
        default => 'No fue posible procesar la operación de rol.',
    };
    mostrarErrorRol($mensaje);
}
