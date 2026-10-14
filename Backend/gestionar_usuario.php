<?php
declare(strict_types=1);

require_once __DIR__ . '/validacion_usuario.php';
$administrador = exigirAdministrador();

function volverAlFormulario(?int $id, string $error): void
{
    $url = '../Frontend/gestionarUsuario.php?error=' . rawurlencode($error);
    if ($id !== null) {
        $url .= '&id=' . $id;
    }
    header('Location: ' . $url, true, 303);
    exit;
}

function volverAUsuarios(string $estado): void
{
    header('Location: ../Frontend/usuarios.php?estado=' . rawurlencode($estado), true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método no permitido.');
}

$id = isset($_POST['id']) && is_scalar($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
$id = $id === false ? null : (int) $id;
$accion = isset($_POST['accion']) && is_string($_POST['accion']) ? $_POST['accion'] : '';
$token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
if (!csrfValido($token)) {
    http_response_code(403);
    exit('La sesión expiró. Vuelve a cargar la página.');
}
if (array_key_exists('id', $_POST) && $id === null) {
    http_response_code(400);
    exit('El identificador del usuario no es válido.');
}

if ($accion === 'eliminar') {
    if ($id === null || $id === (int) $administrador['id']) {
        volverAlFormulario($id, 'protegido');
    }
    try {
        $stmt = db()->prepare('DELETE FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() === 0) {
            volverAUsuarios('no_encontrado');
        }
        volverAUsuarios('eliminado');
    } catch (Throwable $e) {
        error_log('Error al eliminar usuario: ' . $e->getMessage());
        http_response_code(500);
        exit('No se pudo eliminar el usuario.');
    }
}

if (!in_array($accion, ['crear', 'actualizar'], true)) {
    http_response_code(400);
    exit('Acción no válida.');
}
if (($accion === 'crear' && $id !== null) || ($accion === 'actualizar' && $id === null)) {
    volverAlFormulario($id, 'datos');
}

[$datos, $errores] = validarUsuario($_POST, true, $accion === 'actualizar');
$password = $datos['password'];
$claveFormulario = 'gestion:' . ($id ?? 'nuevo');
if ($errores) {
    recordarFormulario($claveFormulario, $datos, $errores);
    volverAlFormulario($id, 'datos');
}
if ($id === (int) $administrador['id'] && $datos['rol'] !== 'administrador') {
    recordarFormulario($claveFormulario, $datos, ['rol' => 'No puedes quitarte tu propio rol de administrador.']);
    volverAlFormulario($id, 'protegido');
}

try {
    $passwordHash = $password === '' ? null : password_hash($password, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new RuntimeException('No se pudo generar el hash de contraseña.');
    }
    $key = aesKey();
    $ivCurp = random_bytes(16);
    $ivCuenta = random_bytes(16);
    $ivSalario = random_bytes(16);

    $pdo = db();
    if ($accion === 'crear') {
        $stmt = $pdo->prepare(<<<'SQL'
            INSERT INTO usuarios (
                nombres, apellidos, fecha_nacimiento, genero, estado_civil, email,
                telefono, direccion, rol, fecha_contratacion, nombre_usuario, password_hash,
                curp_cifrada, curp_iv, cuenta_bancaria_cifrada, cuenta_bancaria_iv,
                salario_cifrado, salario_iv
            ) VALUES (
                :nombres, :apellidos, :fecha_nacimiento, :genero, :estado_civil, :email,
                :telefono, :direccion, :rol, :fecha_contratacion, :nombre_usuario, :password_hash,
                AES_ENCRYPT(:curp, :key_curp, :iv_curp), :iv_curp_store,
                AES_ENCRYPT(:cuenta, :key_cuenta, :iv_cuenta), :iv_cuenta_store,
                AES_ENCRYPT(:salario, :key_salario, :iv_salario), :iv_salario_store
            )
            SQL);
    } else {
        $passwordSet = $passwordHash === null ? '' : ', password_hash = :password_hash';
        $stmt = $pdo->prepare(<<<SQL
            UPDATE usuarios SET
                nombres = :nombres, apellidos = :apellidos, fecha_nacimiento = :fecha_nacimiento,
                genero = :genero, estado_civil = :estado_civil, email = :email,
                telefono = :telefono, direccion = :direccion, rol = :rol,
                fecha_contratacion = :fecha_contratacion, nombre_usuario = :nombre_usuario
                {$passwordSet},
                curp_cifrada = AES_ENCRYPT(:curp, :key_curp, :iv_curp), curp_iv = :iv_curp_store,
                cuenta_bancaria_cifrada = AES_ENCRYPT(:cuenta, :key_cuenta, :iv_cuenta), cuenta_bancaria_iv = :iv_cuenta_store,
                salario_cifrado = AES_ENCRYPT(:salario, :key_salario, :iv_salario), salario_iv = :iv_salario_store
            WHERE id = :id
            SQL);
    }

    $stmt->bindValue(':nombres', $datos['nombres']);
    $stmt->bindValue(':apellidos', $datos['apellidos']);
    $stmt->bindValue(':fecha_nacimiento', $datos['fecha_nacimiento']);
    $stmt->bindValue(':genero', $datos['genero']);
    $stmt->bindValue(':estado_civil', $datos['estado_civil']);
    $stmt->bindValue(':email', $datos['email']);
    $stmt->bindValue(':telefono', $datos['telefono']);
    $stmt->bindValue(':direccion', $datos['direccion']);
    $stmt->bindValue(':rol', $datos['rol']);
    $stmt->bindValue(':fecha_contratacion', $datos['fecha_contratacion']);
    $stmt->bindValue(':nombre_usuario', $datos['nombre_usuario']);
    if ($passwordHash !== null) {
        $stmt->bindValue(':password_hash', $passwordHash);
    }
    $stmt->bindValue(':curp', $datos['curp']);
    $stmt->bindValue(':key_curp', $key, PDO::PARAM_LOB);
    $stmt->bindValue(':iv_curp', $ivCurp, PDO::PARAM_LOB);
    $stmt->bindValue(':iv_curp_store', $ivCurp, PDO::PARAM_LOB);
    $stmt->bindValue(':cuenta', $datos['cuenta_bancaria']);
    $stmt->bindValue(':key_cuenta', $key, PDO::PARAM_LOB);
    $stmt->bindValue(':iv_cuenta', $ivCuenta, PDO::PARAM_LOB);
    $stmt->bindValue(':iv_cuenta_store', $ivCuenta, PDO::PARAM_LOB);
    $stmt->bindValue(':salario', number_format((float) $datos['salario'], 2, '.', ''));
    $stmt->bindValue(':key_salario', $key, PDO::PARAM_LOB);
    $stmt->bindValue(':iv_salario', $ivSalario, PDO::PARAM_LOB);
    $stmt->bindValue(':iv_salario_store', $ivSalario, PDO::PARAM_LOB);
    if ($accion === 'actualizar') {
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    }
    $stmt->execute();
    if ($accion === 'actualizar' && $stmt->rowCount() === 0) {
        $exists = $pdo->prepare('SELECT 1 FROM usuarios WHERE id = :id');
        $exists->execute(['id' => $id]);
        if (!$exists->fetchColumn()) {
            volverAlFormulario(null, 'datos');
        }
    }
} catch (PDOException $e) {
    error_log('Error al guardar usuario: ' . $e->getMessage());
    if ($e->getCode() === '23000') {
        recordarFormulario($claveFormulario, $datos, ['general' => 'El correo o nombre de usuario ya está registrado.']);
        volverAlFormulario($id, 'duplicado');
    }
    recordarFormulario($claveFormulario, $datos, ['general' => 'No se pudo guardar el usuario. Intenta de nuevo.']);
    volverAlFormulario($id, 'servidor');
} catch (Throwable $e) {
    error_log('Error al gestionar usuario: ' . $e->getMessage());
    recordarFormulario($claveFormulario, $datos, ['general' => 'No se pudo guardar el usuario. Intenta de nuevo.']);
    volverAlFormulario($id, 'servidor');
}

volverAUsuarios($accion === 'crear' ? 'creado' : 'actualizado');
