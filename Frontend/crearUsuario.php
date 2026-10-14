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
    <script src="../main.js" defer></script>
</head>
<body>
<main>
    <h1>Registro de nuevo usuario</h1>
    <?php if ($registrado): ?>
        <p role="status">Usuario registrado correctamente. Ya puedes <a href="index.html">iniciar sesión</a>.</p>
    <?php endif; ?>
    <?php require __DIR__ . '/_erroresFormulario.php'; ?>
    <form id="form_crear_usuario" action="../Backend/registrar_usuario.php" method="post" data-formulario-usuario>
        <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
        <?php require __DIR__ . '/_camposUsuario.php'; ?>
        <button type="submit">Registrar usuario</button>
    </form>
    <p><a href="index.html">Volver al login</a></p>
</main>
</body>
</html>
