<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Backend/auth.php';
$usuario = exigirSesion();
$esAdmin = $usuario['rol'] === 'administrador';
$usuarios = [];
$errorListado = false;

try {
    $pdo = db();
    if ($esAdmin) {
        $key = aesKey();
        $stmt = $pdo->prepare(<<<'SQL'
            SELECT id, nombres, apellidos, fecha_nacimiento, genero, estado_civil,
                   email, telefono, direccion, rol, fecha_contratacion, nombre_usuario,
                   CAST(AES_DECRYPT(curp_cifrada, :key_curp, curp_iv) AS CHAR) AS curp,
                   CAST(AES_DECRYPT(cuenta_bancaria_cifrada, :key_cuenta, cuenta_bancaria_iv) AS CHAR) AS cuenta_bancaria,
                   CAST(AES_DECRYPT(salario_cifrado, :key_salario, salario_iv) AS CHAR) AS salario
            FROM usuarios
            ORDER BY id DESC
            SQL);
        $stmt->bindValue(':key_curp', $key, PDO::PARAM_LOB);
        $stmt->bindValue(':key_cuenta', $key, PDO::PARAM_LOB);
        $stmt->bindValue(':key_salario', $key, PDO::PARAM_LOB);
        $stmt->execute();
        $usuarios = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query(
            "SELECT id, nombres, apellidos, email, telefono, fecha_contratacion, nombre_usuario
             FROM usuarios WHERE rol = 'empleado' ORDER BY apellidos, nombres"
        );
        $usuarios = $stmt->fetchAll();
    }
} catch (Throwable $e) {
    error_log('Error al consultar usuarios: ' . $e->getMessage());
    $errorListado = true;
}

$mensajes = [
    'creado' => 'Usuario creado correctamente.',
    'actualizado' => 'Usuario actualizado correctamente.',
    'eliminado' => 'Usuario eliminado correctamente.',
];
$estado = isset($_GET['estado']) && is_string($_GET['estado']) ? ($_GET['estado']) : '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<main>
    <header>
        <h1>Usuarios</h1>
        <p>Sesión: <?= escapar($usuario['nombre_usuario']) ?> · Rol: <?= escapar($usuario['rol']) ?></p>
        <form action="../Backend/logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
            <button type="submit">Cerrar sesión</button>
        </form>
    </header>

    <?php if (isset($mensajes[$estado])): ?>
        <p role="status"><?= escapar($mensajes[$estado]) ?></p>
    <?php endif; ?>
    <?php if ($errorListado): ?>
        <p role="alert">No se pudo cargar la lista de usuarios. Revisa la conexión con Azure.</p>
    <?php endif; ?>

    <?php if ($esAdmin): ?>
        <p>Como administrador puedes consultar los datos protegidos y gestionar las cuentas.</p>
        <p><a href="gestionarUsuario.php">Agregar usuario</a></p>
        <div style="overflow-x:auto">
            <table border="1" cellpadding="6">
                <thead><tr>
                    <th>ID</th><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Correo</th><th>Teléfono</th>
                    <th>Fecha nacimiento</th><th>Género</th><th>Estado civil</th><th>Dirección</th>
                    <th>Contratación</th><th>CURP</th><th>Cuenta bancaria</th><th>Salario</th><th>Acciones</th>
                </tr></thead>
                <tbody>
                <?php foreach ($usuarios as $fila): ?>
                    <tr>
                        <td><?= escapar($fila['id']) ?></td>
                        <td><?= escapar($fila['nombres'] . ' ' . $fila['apellidos']) ?></td>
                        <td><?= escapar($fila['nombre_usuario']) ?></td>
                        <td><?= escapar($fila['rol']) ?></td>
                        <td><?= escapar($fila['email']) ?></td>
                        <td><?= escapar($fila['telefono']) ?></td>
                        <td><?= escapar($fila['fecha_nacimiento']) ?></td>
                        <td><?= escapar($fila['genero']) ?></td>
                        <td><?= escapar($fila['estado_civil']) ?></td>
                        <td><?= escapar($fila['direccion']) ?></td>
                        <td><?= escapar($fila['fecha_contratacion']) ?></td>
                        <td><?= escapar($fila['curp']) ?></td>
                        <td><?= escapar($fila['cuenta_bancaria']) ?></td>
                        <td><?= escapar($fila['salario']) ?></td>
                        <td>
                            <a href="gestionarUsuario.php?id=<?= escapar($fila['id']) ?>">Editar</a>
                            <form action="../Backend/gestionar_usuario.php" method="post" onsubmit="return confirm('¿Eliminar este usuario?');">
                                <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= escapar($fila['id']) ?>">
                                <button type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Vista de solo lectura. Se muestran únicamente los empleados y los datos no cifrados.</p>
        <div style="overflow-x:auto">
            <table border="1" cellpadding="6">
                <thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Teléfono</th><th>Contratación</th></tr></thead>
                <tbody>
                <?php foreach ($usuarios as $fila): ?>
                    <tr>
                        <td><?= escapar($fila['nombres'] . ' ' . $fila['apellidos']) ?></td>
                        <td><?= escapar($fila['nombre_usuario']) ?></td>
                        <td><?= escapar($fila['email']) ?></td>
                        <td><?= escapar($fila['telefono']) ?></td>
                        <td><?= escapar($fila['fecha_contratacion']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
