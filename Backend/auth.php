<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/conexion.php';

function iniciarSesionApp(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function usuarioActual(): ?array
{
    iniciarSesionApp();
    return isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])
        ? $_SESSION['usuario']
        : null;
}

function exigirSesion(): array
{
    $usuario = usuarioActual();
    if ($usuario === null) {
        header('Location: /Frontend/index.html');
        exit;
    }

    try {
        $stmt = db()->prepare('SELECT id, nombre_usuario, rol FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $usuario['id']]);
        $usuarioActualizado = $stmt->fetch();
    } catch (Throwable $e) {
        error_log('Error al validar la sesión: ' . $e->getMessage());
        http_response_code(503);
        exit('No fue posible validar la sesión con la base de datos.');
    }

    if (!$usuarioActualizado) {
        iniciarSesionApp();
        $_SESSION = [];
        session_destroy();
        header('Location: /Frontend/index.html');
        exit;
    }

    $_SESSION['usuario'] = [
        'id' => (int) $usuarioActualizado['id'],
        'nombre_usuario' => $usuarioActualizado['nombre_usuario'],
        'rol' => $usuarioActualizado['rol'],
    ];
    return $_SESSION['usuario'];
}

function exigirAdministrador(): array
{
    $usuario = exigirSesion();
    if (($usuario['rol'] ?? '') !== 'administrador') {
        http_response_code(403);
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>Acceso denegado</title><link rel="stylesheet" href="../styles.css"></head>';
        echo '<body class="auth-page"><main class="card auth-card"><h1>Acceso denegado</h1>';
        echo '<p class="alert alert-error" role="alert">Esta acción requiere permisos de administrador.</p>';
        echo '<a class="button" href="../Frontend/usuarios.php">Volver a usuarios</a></main>';
        require dirname(__DIR__) . '/Frontend/_piePagina.php';
        echo '</body></html>';
        exit;
    }

    return $usuario;
}

function tokenCsrf(): string
{
    iniciarSesionApp();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfValido(?string $token): bool
{
    iniciarSesionApp();
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function escapar(string|int|float|null $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function longitudTexto(string $valor): int
{
    return function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8')
        : (preg_match_all('/./us', $valor, $coincidencias) ?: strlen($valor));
}
