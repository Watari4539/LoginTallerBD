<?php
declare(strict_types=1);

require_once __DIR__ . '/validacion_usuario.php';

function volverRegistro(array $datos = [], ?string $error = null): void
{
    if ($error === null) {
        header('Location: ../Frontend/crearUsuario.php?estado=registrado', true, 303);
    } else {
        recordarFormulario('registro', $datos, ['general' => $error]);
        header('Location: ../Frontend/crearUsuario.php', true, 303);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método no permitido.');
}

[$datos, $errores] = validarUsuario($_POST, false);
$token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
if (!csrfValido($token)) {
    $errores['general'] = 'El formulario expiró. Revisa los datos y vuelve a enviar.';
}
if ($errores) {
    recordarFormulario('registro', $datos, $errores);
    header('Location: ../Frontend/crearUsuario.php', true, 303);
    exit;
}

// El alta pública siempre crea empleados; nunca se confía en un rol enviado por el navegador.
$rol = 'empleado';
try {
    $passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        volverRegistro($datos, 'No se pudo proteger la contraseña. Intenta más tarde.');
    }

    $ivCurp = random_bytes(16);
    $ivCuenta = random_bytes(16);
    $ivSalario = random_bytes(16);

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
        volverRegistro($datos, 'El correo o nombre de usuario ya está registrado.');
    }
    volverRegistro($datos, 'No se pudo guardar el usuario. Intenta de nuevo más tarde.');
} catch (Throwable $e) {
    error_log('Error en el backend de registro: ' . $e->getMessage());
    volverRegistro($datos, 'No se pudo completar el registro. Intenta de nuevo más tarde.');
}

volverRegistro();
