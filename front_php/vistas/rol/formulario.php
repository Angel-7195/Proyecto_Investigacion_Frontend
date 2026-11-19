<?php

declare(strict_types=1);

function mostrarFormularioRol(array $rol, string $csrf, array $errores = []): void
{
    $id = (int) ($rol['id'] ?? 0);
    $titulo = $id ? 'Editar rol' : 'Nuevo rol';
    $accion = $id ? '/roles/actualizar' : '/roles/crear';

    ob_start();
    ?>
    <h1><?= $titulo ?></h1>
    <?php if ($errores !== []): ?>
        <ul class="alert alert-warning" role="alert">
            <?php foreach ($errores as $error): ?>
                <li><?= escaparRol($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post" action="<?= $accion ?>">
        <input type="hidden" name="csrf_token" value="<?= escaparRol($csrf) ?>">
        <?php if ($id): ?>
            <input type="hidden" name="id" value="<?= $id ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label" for="rol-nombre">Nombre</label>
            <input class="form-control" id="rol-nombre" name="nombre" required maxlength="100"
                   value="<?= escaparRol($rol['nombre'] ?? '') ?>"
                   <?= ($rol['nombre'] ?? '') === 'admin' ? 'readonly' : '' ?>>
        </div>
        <div class="mb-3">
            <label class="form-label" for="rol-descripcion">Descripción (opcional)</label>
            <textarea class="form-control" id="rol-descripcion" name="descripcion"
                      rows="4"><?= escaparRol($rol['descripcion'] ?? '') ?></textarea>
        </div>
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn btn-outline-secondary" href="/roles">Cancelar</a>
    </form>
    <?php
    renderizarPlantilla($titulo, ob_get_clean());
}

function mostrarConfirmacionRetiroRol(array $rol, string $csrf): void
{
    ob_start();
    ?>
    <h1>Retirar rol</h1>
    <p>¿Confirmas que deseas retirar el rol <strong><?= escaparRol($rol['nombre']) ?></strong>?</p>
    <form method="post" action="/roles/retirar">
        <input type="hidden" name="csrf_token" value="<?= escaparRol($csrf) ?>">
        <input type="hidden" name="id" value="<?= (int) $rol['id'] ?>">
        <button class="btn btn-danger" type="submit">Confirmar retiro</button>
        <a class="btn btn-outline-secondary" href="/roles">Cancelar</a>
    </form>
    <?php
    renderizarPlantilla('Retirar rol', ob_get_clean());
}
