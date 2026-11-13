<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';

/**
 * Listado y asignación administrativa de roles a usuarios activos.
 * Los roles iniciales (admin ID 1 y consulta ID 2) fueron confirmados en la BD.
 * Cuando se integre el CRUD de rol, este selector podrá usar ese catálogo.
 */
function mostrarGestionRolUsuario(
    array $respuestaAsignaciones,
    array $respuestaUsuarios,
    array $errores = [],
    array $valores = []
): void {
    $_SESSION['csrf_rol_usuario'] ??= bin2hex(random_bytes(32));
    $csrf = htmlspecialchars((string) $_SESSION['csrf_rol_usuario'], ENT_QUOTES, 'UTF-8');

    $asignaciones = $respuestaAsignaciones['datos']['datos'] ?? [];
    $usuarios = $respuestaUsuarios['datos']['datos'] ?? [];
    if (!is_array($asignaciones)) {
        $asignaciones = [];
    }
    if (!is_array($usuarios)) {
        $usuarios = [];
    }

    $usuariosPorId = [];
    foreach ($usuarios as $usuario) {
        if (is_array($usuario) && (int) ($usuario['id'] ?? 0) > 0) {
            $usuariosPorId[(int) $usuario['id']] = (string) ($usuario['username'] ?? '');
        }
    }
    $puedeAsignar = $respuestaUsuarios['correcta'] && $usuariosPorId !== [];
    $usuarioSeleccionado = (int) ($valores['usuario_id'] ?? 0);
    $rolSeleccionado = (int) ($valores['rol_id'] ?? 0);
    $nombresRoles = [1 => 'admin', 2 => 'consulta'];
    $totalAdmins = 0;
    foreach ($asignaciones as $relacion) {
        if (is_array($relacion) && (int) ($relacion['rol_id'] ?? 0) === 1) {
            $totalAdmins++;
        }
    }

    $mensaje = $_SESSION['mensaje_rol_usuario'] ?? null;
    unset($_SESSION['mensaje_rol_usuario']);

    ob_start();
    ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
      <div>
        <h1 class="h2 mb-1">Administración de roles por usuario</h1>
        <p class="text-muted mb-0">Consulta, asigna y retira roles de cuentas activas.</p>
      </div>
      <a href="/usuarios" class="btn btn-outline-secondary">Volver a usuarios</a>
    </div>

    <?php if (is_string($mensaje) && $mensaje !== ''): ?>
      <div class="alert alert-success" role="status">
        <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <?php if ($errores !== []): ?>
      <div class="alert alert-danger" role="alert">
        <strong>Revisa la asignación:</strong>
        <ul class="mb-0 mt-2">
          <?php foreach ($errores as $error): ?>
            <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
      <div class="card-body p-4">
        <h2 class="h4 mb-3">Asignar un rol</h2>
        <?php if (!$respuestaUsuarios['disponible']): ?>
          <div class="alert alert-warning">No fue posible conectar con la API de usuarios.</div>
        <?php elseif (!$respuestaUsuarios['correcta'] || !$puedeAsignar): ?>
          <div class="alert alert-warning">No hay usuarios activos disponibles para asignación o no tienes acceso al listado.</div>
        <?php endif; ?>

        <form method="POST" action="/roles-usuarios/asignar">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <div class="row g-3">
            <div class="col-md-7">
              <label for="usuario_id" class="form-label">Usuario activo</label>
              <select id="usuario_id" name="usuario_id" class="form-select" required
                      <?= $puedeAsignar ? '' : 'disabled' ?>>
                <option value="">Selecciona un usuario</option>
                <?php foreach ($usuariosPorId as $id => $username): ?>
                  <option value="<?= $id ?>" <?= $usuarioSeleccionado === $id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?> (ID <?= $id ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5">
              <label for="rol_id" class="form-label">Rol</label>
              <select id="rol_id" name="rol_id" class="form-select" required
                      <?= $puedeAsignar ? '' : 'disabled' ?>>
                <option value="">Selecciona un rol</option>
                <option value="2" <?= $rolSeleccionado === 2 ? 'selected' : '' ?>>Consulta — solo lectura (ID 2)</option>
                <option value="1" <?= $rolSeleccionado === 1 ? 'selected' : '' ?>>Admin — acceso completo (ID 1)</option>
              </select>
            </div>
          </div>

          <div class="alert alert-warning small mt-3 mb-3">
            <strong>Atención:</strong> asignar <code>admin</code> concede permisos de administración.
            Marca la siguiente casilla si elegiste ese rol.
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="confirmar_admin"
                   name="confirmar_admin" value="1">
            <label class="form-check-label" for="confirmar_admin">
              Confirmo que quiero autorizar permisos de administrador si seleccioné el rol admin.
            </label>
          </div>

          <button type="submit" class="btn btn-primary" <?= $puedeAsignar ? '' : 'disabled' ?>>
            Asignar rol
          </button>
        </form>

        <p class="form-text mt-3 mb-0">
          Este módulo administra asignaciones; la creación o edición de los tipos de roles
          corresponde al módulo <code>rol</code>. Por ahora se usan los ID 1 y 2 confirmados
          en tu base de datos.
        </p>
      </div>
    </div>

    <h2 class="h4 mb-3">Asignaciones activas</h2>
    <?php if (!$respuestaAsignaciones['disponible']): ?>
      <div class="alert alert-warning" role="alert">No se pudo conectar con la API de asignaciones.</div>
    <?php elseif (in_array((int) $respuestaAsignaciones['estado'], [401, 403], true)): ?>
      <div class="alert alert-danger" role="alert">Tu sesión no tiene autorización vigente para consultar las asignaciones.</div>
    <?php elseif (!$respuestaAsignaciones['correcta']): ?>
      <div class="alert alert-danger" role="alert">No se pudo cargar el listado de asignaciones.</div>
    <?php elseif ($asignaciones === []): ?>
      <div class="alert alert-info">Todavía no hay asignaciones activas.</div>
    <?php else: ?>
      <p class="text-muted">Asignaciones encontradas: <?= count($asignaciones) ?></p>
      <div class="table-responsive bg-white rounded shadow-sm">
        <table class="table table-striped table-hover align-middle mb-0">
          <thead class="table-dark">
            <tr>
              <th>ID de usuario</th>
              <th>Nombre de usuario</th>
              <th>ID de rol</th>
              <th>Rol asignado</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($asignaciones as $asignacion): ?>
            <?php
              if (!is_array($asignacion)) { continue; }
              $idUsuario = (int) ($asignacion['usuario_id'] ?? 0);
              $idRol = (int) ($asignacion['rol_id'] ?? 0);
              $username = $usuariosPorId[$idUsuario] ?? 'Usuario no disponible';
              $nombreRol = $nombresRoles[$idRol] ?? ('Rol ID ' . $idRol);
            ?>
            <tr>
              <td><?= $idUsuario ?></td>
              <td><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= $idRol ?></td>
              <td>
                <span class="badge <?= $idRol === 1 ? 'bg-danger' : 'bg-secondary' ?>">
                  <?= htmlspecialchars($nombreRol, ENT_QUOTES, 'UTF-8') ?>
                </span>
              </td>
              <td class="text-end">
                <?php if ($idRol === 1 && $totalAdmins <= 1): ?>
                  <span class="badge bg-light text-dark border" title="Debe permanecer al menos un administrador activo">
                    Último admin protegido
                  </span>
                <?php else: ?>
                  <a class="btn btn-sm btn-outline-danger"
                     href="/roles-usuarios/confirmar-retiro?usuario_id=<?= $idUsuario ?>&amp;rol_id=<?= $idRol ?>">
                    Retirar rol
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
    <?php
    renderizarPlantilla('Roles por usuario', (string) ob_get_clean());
}

/** Confirmación de retiro de una única pareja usuario/rol. */
function mostrarConfirmacionRetiroRolUsuario(
    int $usuarioId,
    int $rolId,
    string $username,
    array $errores = []
): void {
    $claveSesion = $usuarioId . ':' . $rolId;
    $_SESSION['csrf_retiro_rol_usuario'][$claveSesion] ??= bin2hex(random_bytes(32));
    $csrf = htmlspecialchars(
        (string) $_SESSION['csrf_retiro_rol_usuario'][$claveSesion],
        ENT_QUOTES,
        'UTF-8'
    );
    $nombreSeguro = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $nombreRol = $rolId === 1 ? 'admin' : ($rolId === 2 ? 'consulta' : 'Rol ID ' . $rolId);
    $rolSeguro = htmlspecialchars($nombreRol, ENT_QUOTES, 'UTF-8');

    ob_start();
    ?>
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <h1 class="h2 mb-3">Retirar rol de usuario</h1>

        <?php if ($errores !== []): ?>
          <div class="alert alert-danger" role="alert">
            <strong>No se pudo retirar el rol:</strong>
            <ul class="mb-0 mt-2">
              <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="alert alert-warning" role="alert">
          <strong>Atención:</strong> esta operación retirará el rol indicado, no eliminará
          la cuenta del usuario. Los permisos de este rol se retirarán inmediatamente.
          Si la cuenta queda sin roles activos, no podrá iniciar una sesión nueva.
        </div>

        <div class="card shadow-sm"><div class="card-body p-4">
          <dl class="row mb-4">
            <dt class="col-sm-5">ID de usuario</dt>
            <dd class="col-sm-7"><?= $usuarioId ?></dd>
            <dt class="col-sm-5">Nombre de usuario</dt>
            <dd class="col-sm-7"><?= $nombreSeguro ?></dd>
            <dt class="col-sm-5">ID de rol</dt>
            <dd class="col-sm-7"><?= $rolId ?></dd>
            <dt class="col-sm-5">Rol que se retirará</dt>
            <dd class="col-sm-7"><?= $rolSeguro ?></dd>
          </dl>

          <p><strong>¿Confirmas que deseas retirar esta asignación?</strong></p>
          <form method="POST" action="/roles-usuarios/retirar">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="usuario_id" value="<?= $usuarioId ?>">
            <input type="hidden" name="rol_id" value="<?= $rolId ?>">
            <div class="d-flex flex-wrap gap-2">
              <button type="submit" class="btn btn-danger">Sí, retirar rol</button>
              <a href="/roles-usuarios" class="btn btn-outline-secondary">Cancelar</a>
            </div>
          </form>
        </div></div>

        <p class="form-text mt-3">
          Por seguridad, el backend impide retirar el rol al último administrador activo.
        </p>
      </div>
    </div>
    <?php
    renderizarPlantilla('Confirmar retiro de rol', (string) ob_get_clean());
}
