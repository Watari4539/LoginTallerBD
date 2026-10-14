<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function datosUsuarioVacios(): array
{
    return array_fill_keys([
        'nombres', 'apellidos', 'fecha_nacimiento', 'genero', 'estado_civil', 'email',
        'telefono', 'direccion', 'fecha_contratacion', 'nombre_usuario',
        'curp', 'cuenta_bancaria', 'salario',
    ], '') + ['rol' => 'empleado'];
}

// Ambos formularios usan las mismas reglas; la contraseña se conserva literalmente.
function validarUsuario(array $entrada, bool $administrativo, bool $editar = false): array
{
    $datos = datosUsuarioVacios();
    $errores = [];
    foreach ($datos as $campo => $valor) {
        if ($campo === 'rol' && !$administrativo) {
            continue;
        }
        $datos[$campo] = isset($entrada[$campo]) && is_string($entrada[$campo])
            ? trim($entrada[$campo]) : '';
        if ($datos[$campo] === '') {
            $errores[$campo] = 'Completa este campo.';
        }
    }
    $datos['curp'] = strtoupper($datos['curp']);
    $datos['password'] = isset($entrada['password']) && is_string($entrada['password'])
        ? $entrada['password'] : '';

    foreach (['nombres' => 100, 'apellidos' => 120, 'direccion' => 255, 'email' => 254] as $campo => $max) {
        if (longitudTexto($datos[$campo]) > $max) {
            $errores[$campo] = "Usa como máximo {$max} caracteres.";
        }
    }
    foreach (['nombres', 'apellidos'] as $campo) {
        if ($datos[$campo] !== '' && !preg_match("/\A[\p{L}\p{M} .'-]+\z/u", $datos[$campo])) {
            $errores[$campo] = 'Usa letras, espacios, puntos, apóstrofes o guiones.';
        }
    }
    foreach (['fecha_nacimiento', 'fecha_contratacion'] as $campo) {
        $fecha = str_contains($datos[$campo], "\0")
            ? false : DateTimeImmutable::createFromFormat('!Y-m-d', $datos[$campo]);
        if ($fecha === false || $fecha->format('Y-m-d') !== $datos[$campo]) {
            $errores[$campo] = 'Escribe una fecha válida.';
        }
    }
    if ($datos['fecha_nacimiento'] > date('Y-m-d')) {
        $errores['fecha_nacimiento'] = 'La fecha de nacimiento no puede ser futura.';
    }
    foreach (['genero' => ['M', 'F', 'O'], 'estado_civil' => ['soltero', 'casado', 'divorciado'],
        'rol' => ['administrador', 'empleado']] as $campo => $permitidos) {
        if (!in_array($datos[$campo], $permitidos, true)) {
            $errores[$campo] = 'Selecciona una opción válida.';
        }
    }
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Escribe un correo electrónico válido.';
    }
    foreach ([
        'telefono' => ['/\A[0-9]{10}\z/', 'El teléfono debe tener 10 dígitos.'],
        'nombre_usuario' => ['/\A[a-zA-Z0-9_.-]{3,50}\z/', 'Usa entre 3 y 50 letras, números, puntos, guiones o guiones bajos.'],
        'curp' => ['/\A[A-Z0-9]{18}\z/', 'La CURP debe tener 18 caracteres alfanuméricos.'],
        'cuenta_bancaria' => ['/\A[0-9]{10,18}\z/', 'La cuenta debe tener entre 10 y 18 dígitos.'],
    ] as $campo => [$patron, $mensaje]) {
        if (!preg_match($patron, $datos[$campo])) {
            $errores[$campo] = $mensaje;
        }
    }
    if (isset($entrada['password']) && !is_string($entrada['password'])) {
        $errores['password'] = 'Escribe una contraseña válida.';
    }
    if ((!$editar || $datos['password'] !== '')
        && (longitudTexto($datos['password']) < 10 || strlen($datos['password']) > 72
            || str_contains($datos['password'], "\0"))) {
        $errores['password'] = 'La contraseña debe tener al menos 10 caracteres, máximo 72 bytes y no contener caracteres nulos.';
    }
    if (!preg_match('/\A[0-9]+(?:\.[0-9]{1,2})?\z/', $datos['salario'])
        || (float) $datos['salario'] > 9999999999.99) {
        $errores['salario'] = 'Usa un salario entre 0 y 9999999999.99, con hasta dos decimales.';
    }
    return [$datos, $errores];
}

function recordarFormulario(string $clave, array $datos, array $errores): void
{
    iniciarSesionApp();
    // Nunca se vuelve a mostrar ni guardar la contraseña en la sesión.
    unset($datos['password']);
    $_SESSION['formularios'][$clave] = ['datos' => $datos, 'errores' => $errores];
}

function recuperarFormulario(string $clave): array
{
    iniciarSesionApp();
    $resultado = $_SESSION['formularios'][$clave] ?? ['datos' => [], 'errores' => []];
    unset($_SESSION['formularios'][$clave]);
    return $resultado;
}
