<?php
declare(strict_types=1);

require_once __DIR__ . '/cliente_api.php';

$recursos = [
    'area_conocimiento' => 'Áreas de conocimiento',
    'objetivo_desarrollo_sostenible' => 'Objetivos de Desarrollo Sostenible',
    'area_aplicacion' => 'Áreas de aplicación',
    'termino_clave' => 'Términos clave',
    'universidad' => 'Universidades',
    'linea_investigacion' => 'Líneas de investigación',
];

function escapar(mixed $valor): string
{
    if (is_array($valor) || is_object($valor)) {
        $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
    }
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$recurso = $_GET['recurso'] ?? '';
$nombreRecurso = $recursos[$recurso] ?? '';
$datos = [];
$error = '';

if ($recurso !== '' && $nombreRecurso !== '') {
    $respuesta = llamarApi('GET', $recurso);
    if ($respuesta['codigo'] >= 200 && $respuesta['codigo'] < 300) {
        $contenido = $respuesta['datos'];
        $datos = $contenido['datos'] ?? [];
        if (!is_array($datos)) {
            $datos = [];
        }
    } else {
        $error = $respuesta['error'] ?: 'No fue posible consultar la API.';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proyecto de Investigación</title>
    <link rel="stylesheet" href="publico/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">Proyecto de Investigación</a>
    </div>
</nav>

<main class="container py-4">
    <?php if ($nombreRecurso === ''): ?>
        <h1 class="mb-2">Panel de investigación</h1>
        <p class="text-secondary">Selecciona el recurso que deseas consultar.</p>
        <div class="row g-3">
            <?php foreach ($recursos as $clave => $nombre): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5"><?= escapar($nombre) ?></h2>
                            <a class="btn btn-primary" href="?recurso=<?= escapar($clave) ?>">
                                Consultar
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <a href="index.php" class="btn btn-outline-secondary mb-3">← Volver</a>
        <h1 class="h3 mb-3"><?= escapar($nombreRecurso) ?></h1>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= escapar($error) ?></div>
        <?php elseif (count($datos) === 0): ?>
            <div class="alert alert-info">No hay registros para mostrar.</div>
        <?php else: ?>
            <p class="text-secondary">Total de registros: <?= count($datos) ?></p>
            <div class="table-responsive bg-white rounded shadow-sm">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <?php foreach (array_keys((array) $datos[0]) as $columna): ?>
                                <th><?= escapar(ucfirst(str_replace('_', ' ', $columna))) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos as $fila): ?>
                            <tr>
                                <?php foreach ((array) $fila as $valor): ?>
                                    <td><?= escapar($valor) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>
<script src="publico/bootstrap.bundle.min.js"></script>
</body>
</html>
