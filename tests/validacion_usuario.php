<?php
declare(strict_types=1);

// Ejecutar desde la raíz: php tests/validacion_usuario.php (no usa Azure).
require_once dirname(__DIR__) . '/Backend/validacion_usuario.php';

$base = [
    'nombres' => 'María José', 'apellidos' => "O'Connor Pérez", 'fecha_nacimiento' => '2000-02-29',
    'genero' => 'F', 'estado_civil' => 'soltero', 'email' => 'maria@example.com',
    'telefono' => '4451234567', 'direccion' => 'Calle 1, Centro', 'rol' => 'empleado',
    'fecha_contratacion' => '2026-01-01', 'nombre_usuario' => 'maria.test',
    'password' => '  clave12345  ', 'curp' => 'aaaa000101maaaaa00',
    'cuenta_bancaria' => '012345678901234567', 'salario' => '15000.50',
];
$comprobaciones = 0;
function comprobar(bool $condicion, string $mensaje): void
{
    global $comprobaciones;
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
    $comprobaciones++;
}

[$datos, $errores] = validarUsuario($base, false);
comprobar(!$errores, 'El registro válido debe aceptarse.');
comprobar($datos['password'] === $base['password'], 'No se deben recortar las contraseñas.');
comprobar($datos['curp'] === strtoupper($base['curp']), 'La CURP debe normalizarse.');
comprobar($datos['cuenta_bancaria'][0] === '0', 'La cuenta debe conservar ceros iniciales.');
[$datos] = validarUsuario(array_replace($base, ['rol' => 'administrador']), false);
comprobar($datos['rol'] === 'empleado', 'El registro público no puede asignar administrador.');

foreach ([
    ['direccion', '   '], ['nombres', 'María123'], ['email', 'correo-invalido'],
    ['telefono', '445123456'], ['nombre_usuario', 'ab'], ['curp', 'ABC'],
    ['cuenta_bancaria', '012345678A'], ['salario', '-1'], ['salario', '1e3'],
    ['salario', '1.001'], ['salario', '10000000000'], ['fecha_nacimiento', '2025-02-29'],
    ['fecha_nacimiento', '9999-01-01'], ['fecha_contratacion', "2026-01-01\0"],
    ['genero', 'X'], ['rol', 'otro'], ['password', 'corta'],
    ['password', str_repeat('ñ', 37)], ['password', "clave12345\0"], ['email', ['correo']],
] as [$campo, $valor]) {
    [, $errores] = validarUsuario(array_replace($base, [$campo => $valor]), true);
    comprobar(isset($errores[$campo]), "Debe rechazarse el valor inválido de {$campo}.");
}
[, $errores] = validarUsuario(array_replace($base, ['password' => '']), true, true);
comprobar(!$errores, 'Editar permite conservar la contraseña.');
[, $errores] = validarUsuario(array_replace($base, ['password' => []]), true, true);
comprobar(isset($errores['password']), 'Una contraseña enviada como array no debe aceptarse.');
[, $errores] = validarUsuario(array_replace($base, ['password' => str_repeat('ñ', 10)]), true);
comprobar(!$errores, 'Una contraseña válida con acentos debe aceptarse.');

recordarFormulario('prueba', $base, ['email' => 'Duplicado.']);
$recuperado = recuperarFormulario('prueba');
comprobar(!isset($recuperado['datos']['password']), 'La sesión no debe conservar la contraseña.');
comprobar($recuperado['datos']['email'] === $base['email'], 'Los demás datos deben recuperarse.');
comprobar(recuperarFormulario('prueba')['datos'] === [], 'Los datos se consumen una sola vez.');
echo "OK: {$comprobaciones} comprobaciones.\n";
