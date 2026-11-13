<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';

/** Formulario de creación: solo ADMIN (la ruta y la API verifican el permiso). */
function mostrarFormularioUsuario(array $valores = [], array $errores = []): void
{
    $_SESSION['csrf_usuario'] ??= bin2hex(random_bytes(32));
    $username = htmlspecialchars((string) ($valores['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars((string) ($valores['email'] ?? ''), ENT_QUOTES, 'UTF-8');
    $nombre = htmlspecialchars((string) ($valores['nombre_completo'] ?? ''), ENT_QUOTES, 'UTF-8');
    $csrf = htmlspecialchars((string) $_SESSION['csrf_usuario'], ENT_QUOTES, 'UTF-8');

    ob_start();
    ?>
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <h1 class="h2 mb-2">Registrar usuario</h1>
            <p class="text-muted mb-4">Crea una cuenta. Después podrás asignarle un rol.</p>

            <?php if ($errores !== []): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Revisa la información:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errores as $error): ?>
                            <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm"><div class="card-body p-4">
                <form method="POST" action="/usuarios/crear">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                    <div class="mb-3">
                        <label class="form-label" for="username">Nombre de usuario</label>
                        <input class="form-control" type="text" id="username" name="username"
                               value="<?= $username ?>" maxlength="100" autocomplete="off" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Correo electrónico</label>
                        <input class="form-control" type="email" id="email" name="email"
                               value="<?= $email ?>" maxlength="150" autocomplete="off" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="nombre_completo">Nombre completo (opcional)</label>
                        <input class="form-control" type="text" id="nombre_completo" name="nombre_completo"
                               value="<?= $nombre ?>" maxlength="200">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <input class="form-control" type="password" id="password" name="password"
                               autocomplete="new-password" minlength="12" maxlength="72" required>
                        <div class="form-text">Entre 12 y 72 bytes. La contraseña nunca se mostrará en el listado.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="confirmar_password">Confirmar contraseña</label>
                        <input class="form-control" type="password" id="confirmar_password"
                               name="confirmar_password" autocomplete="new-password" required>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit">Registrar usuario</button>
                        <a class="btn btn-outline-secondary" href="/usuarios">Cancelar</a>
                    </div>
                </form>
            </div></div>
        </div>
    </div>
    <?php
    renderizarPlantilla('Registrar usuario', (string) ob_get_clean());
}

/**
 * Edición del perfil: sin campos de contraseña, estado activo ni roles.
 * PUT o PATCH en la API; el formulario HTML utiliza POST en el frontend.
 */
function mostrarFormularioEditarUsuario(array $valores, array $errores = []): void
{
    $_SESSION['csrf_usuario_editar'] ??= bin2hex(random_bytes(32));
    $id = (int) ($valores['id'] ?? 0);
    $username = htmlspecialchars((string) ($valores['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars((string) ($valores['email'] ?? ''), ENT_QUOTES, 'UTF-8');
    $nombre = htmlspecialchars((string) ($valores['nombre_completo'] ?? ''), ENT_QUOTES, 'UTF-8');
    $csrf = htmlspecialchars((string) $_SESSION['csrf_usuario_editar'], ENT_QUOTES, 'UTF-8');

    ob_start();
    ?>
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <h1 class="h2 mb-2">Editar usuario</h1>
        <p class="text-muted mb-4">Puedes guardar la ficha completa (PUT) o solo los campos modificados (PATCH). La contraseña y los roles se gestionan por separado.</p>

        <?php if ($errores !== []): ?>
          <div class="alert alert-danger" role="alert">
            <strong>Revisa la información:</strong>
            <ul class="mb-0 mt-2">
              <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="card shadow-sm"><div class="card-body p-4">
          <form method="POST" action="/usuarios/actualizar">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="mb-3">
              <label class="form-label" for="username">Nombre de usuario</label>
              <input class="form-control" type="text" id="username" name="username"
                     value="<?= $username ?>" maxlength="100" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="email">Correo electrónico</label>
              <input class="form-control" type="email" id="email" name="email"
                     value="<?= $email ?>" maxlength="150" required>
            </div>
            <div class="mb-4">
              <label class="form-label" for="nombre_completo">Nombre completo (opcional)</label>
              <input class="form-control" type="text" id="nombre_completo" name="nombre_completo"
                     value="<?= $nombre ?>" maxlength="200">
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <button type="submit" class="btn btn-primary" name="accion" value="completo">
                Guardar ficha completa (PUT)
              </button>
              <button type="submit" class="btn btn-outline-primary" name="accion" value="parcial">
                Guardar solo cambios (PATCH)
              </button>
              <a class="btn btn-outline-secondary" href="/usuarios">Cancelar</a>
            </div>
          </form>
          <hr class="my-4">
          <p class="mb-2 text-muted">La contraseña se cambia por separado del perfil.</p>
          <a class="btn btn-outline-warning" href="/usuarios/editar?id=<?= $id ?>&amp;seccion=password">
            Cambiar contraseña de este usuario
          </a>
        </div></div>
      </div>
    </div>
    <?php
    renderizarPlantilla('Editar usuario', (string) ob_get_clean());
}

/**
 * Formulario exclusivo para el cambio de contraseña por ADMIN.
 * No expone el hash ni muestra ninguna contraseña al volver a renderizar.
 */
function mostrarFormularioPasswordUsuario(array $usuario, array $errores = []): void
{
    $_SESSION['csrf_usuario_editar'] ??= bin2hex(random_bytes(32));
    $id = (int) ($usuario['id'] ?? 0);
    $username = htmlspecialchars((string) ($usuario['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $csrf = htmlspecialchars((string) $_SESSION['csrf_usuario_editar'], ENT_QUOTES, 'UTF-8');

    ob_start();
    ?>
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <h1 class="h2 mb-2">Cambiar contraseña</h1>
        <p class="text-muted mb-4">
          Estás cambiando la contraseña de <strong><?= $username ?></strong>.
          Esta operación no modifica el nombre, correo ni roles del usuario.
        </p>

        <?php if ($errores !== []): ?>
          <div class="alert alert-danger" role="alert">
            <strong>Revisa la información:</strong>
            <ul class="mb-0 mt-2">
              <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="card shadow-sm"><div class="card-body p-4">
          <form method="POST" action="/usuarios/actualizar" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="accion" value="password">

            <div class="mb-3">
              <label for="nueva_password" class="form-label">Nueva contraseña</label>
              <input class="form-control" type="password" id="nueva_password"
                     name="password" minlength="12" maxlength="72"
                     autocomplete="new-password" required>
              <div class="form-text">Debe contener entre 12 y 72 bytes.</div>
            </div>

            <div class="mb-4">
              <label for="confirmar_nueva_password" class="form-label">Confirmar nueva contraseña</label>
              <input class="form-control" type="password" id="confirmar_nueva_password"
                     name="confirmar_password" minlength="12" maxlength="72"
                     autocomplete="new-password" required>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <button type="submit" class="btn btn-warning">Guardar nueva contraseña</button>
              <a class="btn btn-outline-secondary" href="/usuarios/editar?id=<?= $id ?>">
                Volver al perfil
              </a>
            </div>
          </form>
        </div></div>

        <p class="small text-muted mt-3">
          Si cambias tu propia contraseña de ADMIN, cierra sesión y vuelve a entrar
          con la nueva clave. No compartas la contraseña ni la guardes en capturas.
        </p>
      </div>
    </div>
    <?php
    renderizarPlantilla('Cambiar contraseña de usuario', (string) ob_get_clean());
}

/** Confirmación explícita de desactivación lógica. Solo accesible por ADMIN. */
function mostrarConfirmacionDesactivarUsuario(array $usuario, array $errores = []): void
{
    $_SESSION['csrf_usuario_eliminar'] ??= bin2hex(random_bytes(32));
    $id = (int) ($usuario['id'] ?? 0);
    $username = htmlspecialchars((string) ($usuario['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars((string) ($usuario['email'] ?? ''), ENT_QUOTES, 'UTF-8');
    $csrf = htmlspecialchars((string) $_SESSION['csrf_usuario_eliminar'], ENT_QUOTES, 'UTF-8');

    ob_start();
    ?>
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <h1 class="h2 mb-3">Desactivar usuario</h1>

        <?php if ($errores !== []): ?>
          <div class="alert alert-danger" role="alert">
            <strong>No se pudo completar la operación:</strong>
            <ul class="mb-0 mt-2">
              <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="alert alert-warning" role="alert">
          <strong>Atención:</strong> esta acción desactivará la cuenta.
          Dejará de aparecer en el listado de usuarios activos y perderá el acceso a la API.
          Sus datos permanecerán almacenados en MariaDB.
        </div>

        <div class="card shadow-sm"><div class="card-body p-4">
          <dl class="row mb-4">
            <dt class="col-sm-4">ID</dt>
            <dd class="col-sm-8"><?= $id ?></dd>
            <dt class="col-sm-4">Usuario</dt>
            <dd class="col-sm-8"><?= $username ?></dd>
            <dt class="col-sm-4">Correo electrónico</dt>
            <dd class="col-sm-8"><?= $email ?></dd>
          </dl>

          <p><strong>¿Confirmas que deseas desactivar esta cuenta?</strong></p>

          <form method="POST" action="/usuarios/actualizar">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="username_confirmado" value="<?= $username ?>">

            <div class="d-flex flex-wrap gap-2">
              <button class="btn btn-danger" type="submit">Sí, desactivar usuario</button>
              <a class="btn btn-outline-secondary" href="/usuarios">Cancelar</a>
            </div>
          </form>
        </div></div>

        <p class="small text-muted mt-3">
          Por seguridad, el backend impide desactivar al último administrador activo.
        </p>
      </div>
    </div>
    <?php
    renderizarPlantilla('Confirmar desactivación de usuario', (string) ob_get_clean());
}
