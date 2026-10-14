<?php
declare(strict_types=1);

require_once __DIR__ . '/validacion_usuario.php';
$usuario = exigirAdministrador();
$id = null;
if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        http_response_code(400);
        exit('El identificador del usuario no es válido.');
    }
}
$fila = [
    'nombres' => '', 'apellidos' => '', 'fecha_nacimiento' => '', 'genero' => '',
    'estado_civil' => '', 'email' => '', 'telefono' => '', 'direccion' => '',
    'rol' => 'empleado', 'fecha_contratacion' => '', 'nombre_usuario' => '',
    'curp' => '', 'cuenta_bancaria' => '', 'salario' => '',
];

if ($id !== null) {
    try {
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
    } catch (Throwable $e) {
        error_log('Error al cargar el formulario: ' . $e->getMessage());
        http_response_code(503);
        exit('No se pudo cargar el usuario. Intenta de nuevo más tarde.');
    }
}

$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : '';
$errores = [
    'datos' => 'Revisa los datos ingresados.',
    'duplicado' => 'El correo o nombre de usuario ya está registrado.',
    'protegido' => 'No puedes quitarte tu propio rol de administrador ni eliminar tu cuenta.',
];

$recuperado = recuperarFormulario('gestion:' . ($id ?? 'nuevo'));
$fila = array_replace($fila, $recuperado['datos']);
$erroresCampos = $recuperado['errores'];
if (!$erroresCampos && isset($errores[$error])) {
    $erroresCampos = ['general' => $errores[$error]];
}
$administrativo = true;
$editar = $id !== null;
