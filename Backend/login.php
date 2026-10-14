<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function errorLogin(string $mensaje, int $status = 401): void
{
    http_response_code($status);
    $texto = escapar($mensaje);
    echo "<!doctype html><html lang=\"es\"><meta charset=\"utf-8\"><title>Iniciar sesión</title>";
    echo "<body><main><h1>No se pudo iniciar sesión</h1><p>{$texto}</p>";
    echo '<p><a href="../Frontend/index.html">Volver al login</a></p></main></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    errorLogin('Este endpoint solo acepta el formulario de inicio de sesión.', 405);
}

$nombreUsuario = isset($_POST['usuario']) && is_string($_POST['usuario'])
    ? trim($_POST['usuario'])
    : '';
$password = isset($_POST['password']) && is_string($_POST['password'])
    ? $_POST['password']
    : '';

if ($nombreUsuario === '' || $password === '') {
    errorLogin('Usuario o contraseña incorrectos.');
}

try {
    $stmt = db()->prepare(
        'SELECT id, nombre_usuario, password_hash, rol FROM usuarios WHERE nombre_usuario = :usuario LIMIT 1'
    );
    $stmt->execute(['usuario' => $nombreUsuario]);
    $registro = $stmt->fetch();
} catch (Throwable $e) {
    error_log('Error al iniciar sesión: ' . $e->getMessage());
    errorLogin('No fue posible conectar con el sistema. Intenta de nuevo más tarde.', 500);
}

if (!$registro || !password_verify($password, $registro['password_hash'])) {
    errorLogin('Usuario o contraseña incorrectos.');
}

iniciarSesionApp();
session_regenerate_id(true);
$_SESSION['usuario'] = [
    'id' => (int) $registro['id'],
    'nombre_usuario' => $registro['nombre_usuario'],
    'rol' => $registro['rol'],
];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header('Location: ../Frontend/usuarios.php');
exit;
