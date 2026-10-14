<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Backend/cargar_formulario.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="../main.js" defer></script>
    <title><?= $id === null ? 'Agregar usuario' : 'Editar usuario' ?></title>
</head>
<body>
<main>
    <h1><?= $id === null ? 'Agregar usuario' : 'Editar usuario' ?></h1>
    <?php require __DIR__ . '/_erroresFormulario.php'; ?>
    <p>Administrador: <?= escapar($usuario['nombre_usuario']) ?></p>
    <form action="../Backend/gestionar_usuario.php" method="post" data-formulario-usuario>
        <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
        <input type="hidden" name="accion" value="<?= $id === null ? 'crear' : 'actualizar' ?>">
        <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= escapar($id) ?>"><?php endif; ?>

        <?php require __DIR__ . '/_camposUsuario.php'; ?>
        <button type="submit">Guardar</button>
    </form>
    <p><a href="usuarios.php">Volver a usuarios</a></p>
</main>
</body>
</html>
