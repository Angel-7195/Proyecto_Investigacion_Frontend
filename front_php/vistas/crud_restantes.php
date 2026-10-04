<?php

declare(strict_types=1);

require_once __DIR__ . '/plantilla.php';
require_once __DIR__ . '/../cliente_api.php';

/**
 * Configuración exclusiva de las tres entidades cuya interfaz faltaba.
 * Los nombres de los campos coinciden con el contrato HTTP de la API.
 */
function configuracionCrudRestantes(): array
{
    return [
        'area_conocimiento' => [
            'titulo' => 'Áreas de conocimiento',
            'singular' => 'área de conocimiento',
            'ruta' => '/areas-conocimiento',
            'campos' => [
                'id' => ['etiqueta' => 'Código', 'tipo' => 'text', 'max' => 6, 'clave' => true],
                'gran_area' => ['etiqueta' => 'Gran área', 'tipo' => 'text', 'max' => 60],
                'area' => ['etiqueta' => 'Área', 'tipo' => 'text', 'max' => 60],
                'disciplina' => ['etiqueta' => 'Disciplina', 'tipo' => 'text', 'max' => 150],
            ],
        ],
        'objetivo_desarrollo_sostenible' => [
            'titulo' => 'Objetivos de Desarrollo Sostenible',
            'singular' => 'objetivo de desarrollo sostenible',
            'ruta' => '/objetivos-desarrollo-sostenible',
            'campos' => [
                'id' => ['etiqueta' => 'ID', 'tipo' => 'number', 'clave' => true],
                'nombre' => ['etiqueta' => 'Nombre', 'tipo' => 'text', 'max' => 60],
                'categoria' => ['etiqueta' => 'Categoría', 'tipo' => 'text', 'max' => 45],
            ],
        ],
        'area_aplicacion' => [
            'titulo' => 'Áreas de aplicación',
            'singular' => 'área de aplicación',
            'ruta' => '/areas-aplicacion',
            'campos' => [
                'id' => ['etiqueta' => 'ID', 'tipo' => 'number', 'clave' => true],
                'nombre' => ['etiqueta' => 'Nombre', 'tipo' => 'text', 'max' => 150],
            ],
        ],
    ];
}

function escaparCrudRestante(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Convierte los errores 400/404/409/422 de la API en mensajes legibles. */
function erroresApiCrudRestante(array $respuesta): array
{
    $datos = $respuesta['datos'] ?? [];
    $errores = $datos['errores'] ?? [];
    if (is_array($errores) && $errores !== []) {
        return array_map('strval', $errores);
    }
    if (($respuesta['error'] ?? '') !== '') {
        return [(string) $respuesta['error']];
    }
    $detalle = $datos['detalle'] ?? $datos['mensaje'] ?? null;
    return [$detalle ?: 'No fue posible completar la operación.'];
}

/** Mantiene visible el frontend aunque la API esté apagada. */
function mostrarListaCrudRestante(string $recurso, array $errores = []): void
{
    $config = configuracionCrudRestantes()[$recurso];
    $respuesta = llamarApi('GET', $recurso);
    $codigo = (int) ($respuesta['codigo'] ?? 0);
    $registros = $respuesta['datos']['datos'] ?? [];
    if (!is_array($registros)) {
        $registros = [];
    }
    $mensaje = $_SESSION['mensaje_crud_restante'] ?? '';
    unset($_SESSION['mensaje_crud_restante']);
    ob_start();
    ?>
    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
        <div>
            <h1 class="h2"><?= escaparCrudRestante($config['titulo']) ?></h1>
            <p class="text-muted">Consulte y administre los registros del módulo.</p>
        </div>
        <a class="btn btn-primary" href="<?= escaparCrudRestante($config['ruta']) ?>/nuevo">
            Registrar <?= escaparCrudRestante($config['singular']) ?>
        </a>
    </div>
    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success"><?= escaparCrudRestante($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($errores !== []): ?>
        <div class="alert alert-danger">
            <?php foreach ($errores as $error): ?>
                <div><?= escaparCrudRestante($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($codigo === 0): ?>
        <div class="alert alert-warning">
            El servicio no está disponible en este momento. Puede seguir navegando e intentarlo más tarde.
        </div>
    <?php elseif ($codigo === 204 || ($codigo === 200 && $registros === [])): ?>
        <div class="alert alert-info">Todavía no hay registros.</div>
    <?php elseif ($codigo !== 200 || ($respuesta['error'] ?? '') !== ''): ?>
        <div class="alert alert-danger">
            <?= escaparCrudRestante(implode(' ', erroresApiCrudRestante($respuesta))) ?>
        </div>
    <?php else: ?>
        <p class="text-muted">Total de registros: <?= count($registros) ?></p>
        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark"><tr>
                    <?php foreach ($config['campos'] as $campo): ?>
                        <th><?= escaparCrudRestante($campo['etiqueta']) ?></th>
                    <?php endforeach; ?>
                    <th class="text-end">Acciones</th>
                </tr></thead>
                <tbody>
                <?php foreach ($registros as $registro): ?>
                    <?php
                    if (!is_array($registro)) {
                        continue;
                    }
                    $id = (string) ($registro['id'] ?? '');
                    ?>
                    <tr>
                        <?php foreach ($config['campos'] as $nombre => $campo): ?>
                            <td><?= escaparCrudRestante($registro[$nombre] ?? '') ?></td>
                        <?php endforeach; ?>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary"
                               href="<?= escaparCrudRestante($config['ruta']) ?>/editar?id=<?= rawurlencode($id) ?>">
                               Editar
                            </a>
                            <form class="d-inline" method="POST"
                                  action="<?= escaparCrudRestante($config['ruta']) ?>/retirar"
                                  onsubmit="return confirm('¿Desea retirar este registro?');">
                                <input type="hidden" name="id" value="<?= escaparCrudRestante($id) ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Retirar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php
    renderizarPlantilla($config['titulo'], (string) ob_get_clean());
}

function mostrarFormularioCrudRestante(
    string $recurso,
    string $modo,
    array $valores = [],
    array $errores = []
): void {
    $config = configuracionCrudRestantes()[$recurso];
    $esEdicion = $modo === 'editar';
    $titulo = ($esEdicion ? 'Editar ' : 'Registrar ') . $config['singular'];
    $originales = $valores['original'] ?? $valores;
    $rutaFormulario = $config['ruta'] . ($esEdicion ? '/actualizar' : '/crear');
    ob_start();
    ?>
    <div class="row justify-content-center"><div class="col-lg-8">
        <h1 class="h2 mb-2"><?= escaparCrudRestante(ucfirst($titulo)) ?></h1>
        <p class="text-muted mb-4">Complete los campos obligatorios del registro.</p>
        <?php if ($errores !== []): ?>
            <div class="alert alert-danger">
                <strong>Revise la información ingresada:</strong>
                <ul class="mb-0 mt-2">
                <?php foreach ($errores as $error): ?>
                    <li><?= escaparCrudRestante($error) ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div class="card shadow-sm"><div class="card-body">
            <form method="POST" action="<?= escaparCrudRestante($rutaFormulario) ?>">
            <?php foreach ($config['campos'] as $nombre => $campo): ?>
                <?php
                $esClave = ($campo['clave'] ?? false);
                $valor = (string) ($valores[$nombre] ?? '');
                ?>
                <div class="mb-3">
                    <label class="form-label" for="<?= escaparCrudRestante($nombre) ?>">
                        <?= escaparCrudRestante($campo['etiqueta']) ?>
                    </label>
                    <input class="form-control" id="<?= escaparCrudRestante($nombre) ?>"
                           name="<?= escaparCrudRestante($nombre) ?>"
                           type="<?= escaparCrudRestante($campo['tipo']) ?>"
                           value="<?= escaparCrudRestante($valor) ?>"
                           <?= $campo['tipo'] === 'number' ? 'min="1" step="1"' : '' ?>
                           <?= isset($campo['max']) ? 'maxlength="' . (int) $campo['max'] . '"' : '' ?>
                           <?= $esEdicion && $esClave ? 'readonly' : 'required' ?>>
                    <?php if ($esEdicion && $esClave): ?>
                        <div class="form-text">El identificador no se puede modificar.</div>
                    <?php endif; ?>
                </div>
                <?php if ($esEdicion && !$esClave): ?>
                    <input type="hidden" name="original[<?= escaparCrudRestante($nombre) ?>]"
                           value="<?= escaparCrudRestante($originales[$nombre] ?? $valor) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if (!$esEdicion): ?>
                        <button class="btn btn-primary" type="submit">Registrar</button>
                    <?php else: ?>
                        <button class="btn btn-primary" name="accion" value="completo" type="submit">
                            Guardar ficha completa (PUT)
                        </button>
                        <button class="btn btn-outline-primary" name="accion" value="parcial" type="submit">
                            Guardar solo los cambios (PATCH)
                        </button>
                    <?php endif; ?>
                    <a class="btn btn-outline-secondary" href="<?= escaparCrudRestante($config['ruta']) ?>">
                        Cancelar
                    </a>
                </div>
            </form>
        </div></div>
    </div></div>
    <?php
    renderizarPlantilla(ucfirst($titulo), (string) ob_get_clean());
}

function mostrarErrorCrudRestante(string $titulo, array $errores): void
{
    ob_start();
    ?>
    <h1 class="h2"><?= escaparCrudRestante($titulo) ?></h1>
    <div class="alert alert-warning">
        <?php foreach ($errores as $error): ?>
            <p class="mb-1"><?= escaparCrudRestante($error) ?></p>
        <?php endforeach; ?>
    </div>
    <a class="btn btn-outline-secondary" href="/">Volver al inicio</a>
    <?php
    renderizarPlantilla($titulo, (string) ob_get_clean());
}
