<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Backend/cargar_formulario.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../styles.css">
    <script src="../main.js" defer></script>
    <title><?= $id === null ? 'Agregar usuario' : 'Editar usuario' ?></title>
</head>
<body>
<header class="site-header">
    <nav class="header-content" aria-label="Navegación principal">
        <a class="brand" href="usuarios.php">Gestión de usuarios</a>
        <a href="usuarios.php">Volver a usuarios</a>
    </nav>
</header>
<main class="page page-form">
    <div class="page-heading">
    <h1><?= $id === null ? 'Agregar usuario' : 'Editar usuario' ?></h1>
        <p class="muted">Captura los datos de la cuenta. Los campos son obligatorios, salvo la contraseña al editar.</p>
    </div>
    <?php require __DIR__ . '/_erroresFormulario.php'; ?>
    <p class="muted">Administrador: <?= escapar($usuario['nombre_usuario']) ?></p>
    <form action="../Backend/gestionar_usuario.php" method="post" class="card form-card" data-formulario-usuario>
        <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
        <input type="hidden" name="accion" value="<?= $id === null ? 'crear' : 'actualizar' ?>">
        <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= escapar($id) ?>"><?php endif; ?>

        <?php require __DIR__ . '/_camposUsuario.php'; ?>
        <div class="form-actions">
            <a class="button button-secondary" href="usuarios.php">Cancelar</a>
            <button type="submit">Guardar cambios</button>
        </div>
    </form>
</main>
<?php require __DIR__ . '/_piePagina.php'; ?>
</body>
</html>
