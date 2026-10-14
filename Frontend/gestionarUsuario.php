<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Backend/auth.php';
$usuario = exigirAdministrador();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$fila = [
    'nombres' => '', 'apellidos' => '', 'fecha_nacimiento' => '', 'genero' => '',
    'estado_civil' => '', 'email' => '', 'telefono' => '', 'direccion' => '',
    'rol' => 'empleado', 'fecha_contratacion' => '', 'nombre_usuario' => '',
    'curp' => '', 'cuenta_bancaria' => '', 'salario' => '',
];

if ($id !== null) {
    $key = aesKey();
    $stmt = db()->prepare(<<<'SQL'
        SELECT id, nombres, apellidos, fecha_nacimiento, genero, estado_civil, email,
               telefono, direccion, rol, fecha_contratacion, nombre_usuario,
               CAST(AES_DECRYPT(curp_cifrada, :key_curp, curp_iv) AS CHAR) AS curp,
               CAST(AES_DECRYPT(cuenta_bancaria_cifrada, :key_cuenta, cuenta_bancaria_iv) AS CHAR) AS cuenta_bancaria,
               CAST(AES_DECRYPT(salario_cifrado, :key_salario, salario_iv) AS CHAR) AS salario
        FROM usuarios WHERE id = :id LIMIT 1
        SQL);
    $stmt->bindValue(':key_curp', $key, PDO::PARAM_LOB);
    $stmt->bindValue(':key_cuenta', $key, PDO::PARAM_LOB);
    $stmt->bindValue(':key_salario', $key, PDO::PARAM_LOB);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $record = $stmt->fetch();
    if (!$record) {
        http_response_code(404);
        exit('No se encontró el usuario solicitado.');
    }
    $fila = $record;
}

$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : '';
$errores = [
    'datos' => 'Revisa los datos ingresados.',
    'duplicado' => 'El correo o nombre de usuario ya está registrado.',
    'protegido' => 'No puedes quitarte tu propio rol de administrador ni eliminar tu cuenta.',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $id === null ? 'Agregar usuario' : 'Editar usuario' ?></title>
</head>
<body>
<main>
    <h1><?= $id === null ? 'Agregar usuario' : 'Editar usuario' ?></h1>
    <?php if (isset($errores[$error])): ?><p role="alert"><?= escapar($errores[$error]) ?></p><?php endif; ?>
    <p>Administrador: <?= escapar($usuario['nombre_usuario']) ?></p>
    <form action="../Backend/gestionar_usuario.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
        <input type="hidden" name="accion" value="<?= $id === null ? 'crear' : 'actualizar' ?>">
        <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= escapar($id) ?>"><?php endif; ?>

        <label for="nombres">Nombres</label>
        <input id="nombres" name="nombres" maxlength="100" value="<?= escapar($fila['nombres']) ?>" required>
        <label for="apellidos">Apellidos</label>
        <input id="apellidos" name="apellidos" maxlength="120" value="<?= escapar($fila['apellidos']) ?>" required>
        <label for="fecha_nacimiento">Fecha de nacimiento</label>
        <input id="fecha_nacimiento" type="date" name="fecha_nacimiento" value="<?= escapar($fila['fecha_nacimiento']) ?>" required>
        <label for="genero">Género</label>
        <select id="genero" name="genero" required>
            <option value="">Seleccione...</option>
            <option value="M" <?= $fila['genero'] === 'M' ? 'selected' : '' ?>>Masculino</option>
            <option value="F" <?= $fila['genero'] === 'F' ? 'selected' : '' ?>>Femenino</option>
            <option value="O" <?= $fila['genero'] === 'O' ? 'selected' : '' ?>>Otro</option>
        </select>
        <label for="estado_civil">Estado civil</label>
        <select id="estado_civil" name="estado_civil" required>
            <option value="">Seleccione...</option>
            <option value="soltero" <?= $fila['estado_civil'] === 'soltero' ? 'selected' : '' ?>>Soltero/a</option>
            <option value="casado" <?= $fila['estado_civil'] === 'casado' ? 'selected' : '' ?>>Casado/a</option>
            <option value="divorciado" <?= $fila['estado_civil'] === 'divorciado' ? 'selected' : '' ?>>Divorciado/a</option>
        </select>
        <label for="email">Correo</label>
        <input id="email" type="email" name="email" maxlength="254" value="<?= escapar($fila['email']) ?>" required>
        <label for="telefono">Teléfono (10 dígitos)</label>
        <input id="telefono" name="telefono" inputmode="numeric" maxlength="10" value="<?= escapar($fila['telefono']) ?>" required>
        <label for="direccion">Dirección</label>
        <input id="direccion" name="direccion" maxlength="255" value="<?= escapar($fila['direccion']) ?>" required>
        <label for="rol">Rol</label>
        <select id="rol" name="rol" required>
            <option value="empleado" <?= $fila['rol'] === 'empleado' ? 'selected' : '' ?>>Empleado</option>
            <option value="administrador" <?= $fila['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
        </select>
        <label for="fecha_contratacion">Fecha de contratación</label>
        <input id="fecha_contratacion" type="date" name="fecha_contratacion" value="<?= escapar($fila['fecha_contratacion']) ?>" required>
        <label for="nombre_usuario">Nombre de usuario</label>
        <input id="nombre_usuario" name="nombre_usuario" maxlength="50" value="<?= escapar($fila['nombre_usuario']) ?>" required>
        <label for="password">Contraseña <?= $id === null ? '' : '(deja en blanco para conservarla)' ?></label>
        <input id="password" type="password" name="password" minlength="10" maxlength="72" autocomplete="new-password" <?= $id === null ? 'required' : '' ?>>
        <label for="curp">CURP</label>
        <input id="curp" name="curp" minlength="18" maxlength="18" value="<?= escapar($fila['curp']) ?>" required>
        <label for="cuenta_bancaria">Cuenta bancaria o CLABE</label>
        <input id="cuenta_bancaria" name="cuenta_bancaria" minlength="10" maxlength="18" value="<?= escapar($fila['cuenta_bancaria']) ?>" required>
        <label for="salario">Salario mensual</label>
        <input id="salario" type="number" name="salario" min="0" step="0.01" value="<?= escapar($fila['salario']) ?>" required>
        <button type="submit">Guardar</button>
    </form>
    <p><a href="usuarios.php">Volver a usuarios</a></p>
</main>
</body>
</html>
