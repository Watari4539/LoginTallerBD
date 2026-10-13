<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/conexion.php';

function responder(int $status, string $mensaje): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    $mensajeSeguro = htmlspecialchars($mensaje, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo "<!doctype html><html lang=\"es\"><meta charset=\"utf-8\"><title>Registro</title>";
    echo "<body><main><h1>Registro de usuario</h1><p>{$mensajeSeguro}</p>";
    echo '<p><a href="../Frontend/crearUsuario.html">Volver al formulario</a></p></main></body></html>';
    exit;
}

function longitud(string $valor): int
{
    return function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);
}

function fechaValida(string $valor): bool
{
    $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
    return $fecha !== false && $fecha->format('Y-m-d') === $valor;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responder(405, 'Este endpoint solo acepta envíos del formulario.');
}

$camposRequeridos = [
    'nombres', 'apellidos', 'fecha_nacimiento', 'genero', 'estado_civil', 'email',
    'telefono', 'direccion', 'fecha_contratacion', 'nombre_usuario', 'password',
    'curp', 'cuenta_bancaria', 'salario',
];
foreach ($camposRequeridos as $campo) {
    if (!isset($_POST[$campo]) || !is_string($_POST[$campo]) || trim($_POST[$campo]) === '') {
        responder(422, 'Completa todos los campos obligatorios.');
    }
}

$datos = [];
foreach ($camposRequeridos as $campo) {
    $datos[$campo] = trim($_POST[$campo]);
}

if (longitud($datos['nombres']) > 100 || longitud($datos['apellidos']) > 120) {
    responder(422, 'Nombres o apellidos exceden la longitud permitida.');
}
if (!preg_match("/\\A[\\p{L}\\p{M} .'-]+\\z/u", $datos['nombres'])
    || !preg_match("/\\A[\\p{L}\\p{M} .'-]+\\z/u", $datos['apellidos'])) {
    responder(422, 'Nombres y apellidos solo pueden incluir letras, espacios, puntos, apóstrofes y guiones.');
}
if (longitud($datos['direccion']) > 255 || longitud($datos['email']) > 254) {
    responder(422, 'La dirección o el correo exceden la longitud permitida.');
}
if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
    responder(422, 'Escribe un correo electrónico válido.');
}
if (!in_array($datos['genero'], ['M', 'F', 'O'], true)) {
    responder(422, 'Selecciona un género válido.');
}
if (!in_array($datos['estado_civil'], ['soltero', 'casado', 'divorciado'], true)) {
    responder(422, 'Selecciona un estado civil válido.');
}
if (!fechaValida($datos['fecha_nacimiento']) || $datos['fecha_nacimiento'] > date('Y-m-d')) {
    responder(422, 'La fecha de nacimiento no es válida.');
}
if (!fechaValida($datos['fecha_contratacion'])) {
    responder(422, 'La fecha de contratación no es válida.');
}
if (!preg_match('/\A[0-9]{10}\z/', $datos['telefono'])) {
    responder(422, 'El teléfono debe tener exactamente 10 dígitos.');
}
if (!preg_match('/\A[a-zA-Z0-9_.-]{3,50}\z/', $datos['nombre_usuario'])) {
    responder(422, 'El usuario debe tener entre 3 y 50 caracteres: letras, números, punto, guion o guion bajo.');
}
if (strlen($datos['password']) < 10 || strlen($datos['password']) > 72) {
    responder(422, 'La contraseña debe tener entre 10 y 72 bytes.');
}
$datos['curp'] = strtoupper($datos['curp']);
if (!preg_match('/\A[A-Z0-9]{18}\z/', $datos['curp'])) {
    responder(422, 'El CURP debe tener 18 caracteres alfanuméricos.');
}
if (!preg_match('/\A[0-9]{10,18}\z/', $datos['cuenta_bancaria'])) {
    responder(422, 'La cuenta bancaria debe contener entre 10 y 18 dígitos.');
}
if (!is_numeric($datos['salario']) || (float) $datos['salario'] < 0 || (float) $datos['salario'] > 9999999999.99) {
    responder(422, 'Escribe un salario válido.');
}

// El alta pública siempre crea empleados; nunca se confía en un rol enviado por el navegador.
$rol = 'empleado';
$passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);
if ($passwordHash === false) {
    responder(500, 'No se pudo proteger la contraseña. Intenta más tarde.');
}

$ivCurp = random_bytes(16);
$ivCuenta = random_bytes(16);
$ivSalario = random_bytes(16);

try {
    $pdo = db();
    $sql = <<<'SQL'
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
        SQL;
    $stmt = $pdo->prepare($sql);
    $key = aesKey();
    $stmt->bindValue(':nombres', $datos['nombres']);
    $stmt->bindValue(':apellidos', $datos['apellidos']);
    $stmt->bindValue(':fecha_nacimiento', $datos['fecha_nacimiento']);
    $stmt->bindValue(':genero', $datos['genero']);
    $stmt->bindValue(':estado_civil', $datos['estado_civil']);
    $stmt->bindValue(':email', $datos['email']);
    $stmt->bindValue(':telefono', $datos['telefono']);
    $stmt->bindValue(':direccion', $datos['direccion']);
    $stmt->bindValue(':rol', $rol);
    $stmt->bindValue(':fecha_contratacion', $datos['fecha_contratacion']);
    $stmt->bindValue(':nombre_usuario', $datos['nombre_usuario']);
    $stmt->bindValue(':password_hash', $passwordHash);
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
    $stmt->execute();
} catch (PDOException $e) {
    error_log('Error al registrar usuario: ' . $e->getMessage());
    if ($e->getCode() === '23000') {
        responder(409, 'El correo o nombre de usuario ya está registrado.');
    }
    responder(500, 'No se pudo guardar el usuario. Revisa la configuración de Azure y vuelve a intentar.');
} catch (Throwable $e) {
    error_log('Error en el backend de registro: ' . $e->getMessage());
    responder(500, 'El backend no está configurado. Revisa config.local.php.');
}

responder(201, 'Usuario registrado correctamente con rol de empleado.');
