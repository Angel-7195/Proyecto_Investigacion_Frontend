<?php

declare(strict_types=1);

require_once __DIR__ . '/../compartidas/plantilla.php';

/** Muestra el formulario; la contraseña nunca se vuelve a escribir en HTML. */
function mostrarLogin(string $error = ''): void
{
    $_SESSION['csrf_login'] ??= bin2hex(random_bytes(32));
    $csrf = htmlspecialchars((string) $_SESSION['csrf_login'], ENT_QUOTES, 'UTF-8');
    $errorSeguro = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');

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
                </div>
            </div>
        </div>
    </section>
    <?php
    renderizarPlantilla('Iniciar sesión', (string) ob_get_clean());
}
