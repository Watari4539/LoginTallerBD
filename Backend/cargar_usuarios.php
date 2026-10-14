<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
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
    'no_encontrado' => 'El usuario ya no existe. La lista se ha actualizado.',
];
$estado = isset($_GET['estado']) && is_string($_GET['estado']) ? ($_GET['estado']) : '';
