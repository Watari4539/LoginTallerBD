<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$administrador = exigirAdministrador();

function volverAlFormulario(?int $id, string $error): void
{
    $url = '../Frontend/gestionarUsuario.php?error=' . rawurlencode($error);
    if ($id !== null) {
        $url .= '&id=' . $id;
    }
    header('Location: ' . $url);
    exit;
}

function volverAUsuarios(string $estado): void
{
    header('Location: ../Frontend/usuarios.php?estado=' . rawurlencode($estado));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método no permitido.');
}

$id = isset($_POST['id']) && is_scalar($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : false;
$id = $id === false ? null : (int) $id;
$accion = isset($_POST['accion']) && is_string($_POST['accion']) ? $_POST['accion'] : '';
$token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
if (!csrfValido($token)) {
    http_response_code(403);
    exit('La sesión expiró. Vuelve a cargar la página.');
}

if ($accion === 'eliminar') {
    if ($id === null || $id === (int) $administrador['id']) {
        volverAlFormulario($id, 'protegido');
    }
    try {
        $stmt = db()->prepare('DELETE FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() === 0) {
            volverAUsuarios('');
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

$requeridos = [
    'nombres', 'apellidos', 'fecha_nacimiento', 'genero', 'estado_civil', 'email',
    'telefono', 'direccion', 'rol', 'fecha_contratacion', 'nombre_usuario',
    'curp', 'cuenta_bancaria', 'salario',
];
$datos = [];
foreach ($requeridos as $campo) {
    if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
        volverAlFormulario($id, 'datos');
    }
    $datos[$campo] = trim($_POST[$campo]);
}
$password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

$nombreValido = preg_match("/\\A[\\p{L}\\p{M} .'-]+\\z/u", $datos['nombres'])
    && preg_match("/\\A[\\p{L}\\p{M} .'-]+\\z/u", $datos['apellidos']);
$fechaNacimiento = DateTimeImmutable::createFromFormat('!Y-m-d', $datos['fecha_nacimiento']);
$fechaContratacion = DateTimeImmutable::createFromFormat('!Y-m-d', $datos['fecha_contratacion']);
$fechasValidas = $fechaNacimiento !== false && $fechaNacimiento->format('Y-m-d') === $datos['fecha_nacimiento']
    && $fechaNacimiento->format('Y-m-d') <= date('Y-m-d')
    && $fechaContratacion !== false && $fechaContratacion->format('Y-m-d') === $datos['fecha_contratacion'];
$longitudesValidas = longitudTexto($datos['nombres']) <= 100
    && longitudTexto($datos['apellidos']) <= 120
    && longitudTexto($datos['direccion']) <= 255
    && longitudTexto($datos['email']) <= 254;
$tiposValidos = filter_var($datos['email'], FILTER_VALIDATE_EMAIL)
    && in_array($datos['genero'], ['M', 'F', 'O'], true)
    && in_array($datos['estado_civil'], ['soltero', 'casado', 'divorciado'], true)
    && in_array($datos['rol'], ['administrador', 'empleado'], true)
    && preg_match('/\\A[0-9]{10}\\z/', $datos['telefono'])
    && preg_match('/\\A[a-zA-Z0-9_.-]{3,50}\\z/', $datos['nombre_usuario'])
    && preg_match('/\\A[A-Z0-9]{18}\\z/i', $datos['curp'])
    && preg_match('/\\A[0-9]{10,18}\\z/', $datos['cuenta_bancaria'])
    && is_numeric($datos['salario'])
    && (float) $datos['salario'] >= 0
    && (float) $datos['salario'] <= 9999999999.99;
$passwordValido = $accion === 'actualizar'
    ? ($password === '' || (strlen($password) >= 10 && strlen($password) <= 72))
    : (strlen($password) >= 10 && strlen($password) <= 72);

if (!$nombreValido || !$fechasValidas || !$longitudesValidas || !$tiposValidos || !$passwordValido) {
    volverAlFormulario($id, 'datos');
}
$datos['curp'] = strtoupper($datos['curp']);
if ($id === (int) $administrador['id'] && $datos['rol'] !== 'administrador') {
    volverAlFormulario($id, 'protegido');
}

$passwordHash = $password === '' ? null : password_hash($password, PASSWORD_DEFAULT);
$key = aesKey();
$ivCurp = random_bytes(16);
$ivCuenta = random_bytes(16);
$ivSalario = random_bytes(16);

try {
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
        volverAlFormulario($id, 'duplicado');
    }
    http_response_code(500);
    exit('No se pudo guardar el usuario.');
} catch (Throwable $e) {
    error_log('Error al gestionar usuario: ' . $e->getMessage());
    http_response_code(500);
    exit('Ocurrió un error al gestionar el usuario.');
}

volverAUsuarios($accion === 'crear' ? 'creado' : 'actualizado');
