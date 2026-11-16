<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';

/** Muestra el formulario; nunca vuelve a mostrar la contraseña introducida por el visitante. */
function mostrarLogin(string $error = ''): void
{
    $_SESSION['csrf_login'] ??= bin2hex(random_bytes(32));
    $csrf = htmlspecialchars((string) $_SESSION['csrf_login'], ENT_QUOTES, 'UTF-8');
    $errorSeguro = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');

    // Las credenciales de demostración solo se muestran si se habilitan
    // expresamente en el entorno del frontend. No son datos del JWT ni de la API.
    $modoDemo = getenv('LOGIN_DEMO_ENABLED') === '1';
    $usuarioDemo = trim((string) getenv('LOGIN_DEMO_ADMIN_USERNAME'));
    $claveDemo = getenv('LOGIN_DEMO_ADMIN_PASSWORD');
    $mostrarDemo = $modoDemo && $usuarioDemo !== ''
        && is_string($claveDemo) && $claveDemo !== '';

    if ($mostrarDemo) {
        $usuarioDemoSeguro = htmlspecialchars($usuarioDemo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $claveDemoSeguro = htmlspecialchars($claveDemo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    ob_start();
    ?>
    <section class="row justify-content-center py-5">
        <div class="col-12 col-md-7 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-3">Iniciar sesión</h1>
                    <p class="text-muted">Accede al módulo de Investigación.</p>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger" role="alert"><?= $errorSeguro ?></div>
                    <?php endif; ?>

                    <form method="post" action="/login">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Usuario</label>
                            <input class="form-control" type="text" id="username"
                                   name="username" maxlength="100" autocomplete="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input class="form-control" type="password" id="password"
                                   name="password" autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                    </form>

                    <?php if ($mostrarDemo): ?>
                        <aside class="alert alert-info mt-4 mb-0" aria-label="Credenciales de demostración">
                            <h2 class="h6 alert-heading mb-2">Acceso de demostración</h2>
                            <p class="small mb-2">
                                Cuenta de prueba para la evaluación académica.
                                Utilízala únicamente en un entorno con datos ficticios.
                            </p>
                            <dl class="row small mb-0">
                                <dt class="col-5">Rol</dt>
                                <dd class="col-7">Administrador</dd>
                                <dt class="col-5">Usuario</dt>
                                <dd class="col-7"><code><?= $usuarioDemoSeguro ?></code></dd>
                                <dt class="col-5">Contraseña</dt>
                                <dd class="col-7"><code><?= $claveDemoSeguro ?></code></dd>
                            </dl>
                        </aside>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
    renderizarPlantilla('Iniciar sesión', (string) ob_get_clean());
}
