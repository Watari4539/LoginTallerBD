<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/Backend/validacion_usuario.php';
$recuperado = recuperarFormulario('registro');
$fila = array_replace(datosUsuarioVacios(), $recuperado['datos']);
$erroresCampos = $recuperado['errores'];
$administrativo = false;
$editar = false;
$registrado = isset($_GET['estado']) && $_GET['estado'] === 'registrado';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de usuario</title>
    <link rel="stylesheet" href="../styles.css">
    <script src="../main.js" defer></script>
</head>
<body>
<header class="site-header">
    <nav class="header-content" aria-label="Navegación principal">
        <a class="brand" href="index.html">Gestión de usuarios</a>
        <a href="index.html">Volver al login</a>
    </nav>
</header>
<main class="page page-form">
    <div class="page-heading">
    <h1>Registro de nuevo usuario</h1>
        <p class="muted">Completa tus datos para crear una cuenta de empleado.</p>
    </div>
    <?php if ($registrado): ?>
        <p class="alert alert-success" role="status">Usuario registrado correctamente. Ya puedes <a href="index.html">iniciar sesión</a>.</p>
    <?php endif; ?>
    <?php require __DIR__ . '/_erroresFormulario.php'; ?>
    <form id="form_crear_usuario" action="../Backend/registrar_usuario.php" method="post" class="card form-card" data-formulario-usuario>
        <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
        <?php require __DIR__ . '/_camposUsuario.php'; ?>
        <div class="form-actions">
            <a class="button button-secondary" href="index.html">Volver al login</a>
            <button type="submit">Crear cuenta</button>
        </div>
    </form>
</main>
<?php require __DIR__ . '/_piePagina.php'; ?>
</body>
</html>
