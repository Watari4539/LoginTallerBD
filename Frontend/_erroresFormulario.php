<?php
$etiquetasCampos = [
    'nombres' => 'Nombres', 'apellidos' => 'Apellidos', 'fecha_nacimiento' => 'Fecha de nacimiento',
    'genero' => 'Género', 'estado_civil' => 'Estado civil', 'email' => 'Correo',
    'telefono' => 'Teléfono', 'direccion' => 'Dirección', 'rol' => 'Rol',
    'fecha_contratacion' => 'Fecha de contratación', 'nombre_usuario' => 'Nombre de usuario',
    'password' => 'Contraseña', 'curp' => 'CURP', 'cuenta_bancaria' => 'Cuenta bancaria', 'salario' => 'Salario',
];
?>
<?php if ($erroresCampos): ?>
    <div role="alert">
        <p>No se guardaron los cambios. Corrige lo siguiente y vuelve a escribir la contraseña si corresponde:</p>
        <ul>
        <?php foreach ($erroresCampos as $campo => $mensaje): ?>
            <li>
                <?php if (isset($etiquetasCampos[$campo])): ?>
                    <a href="#<?= escapar($campo) ?>"><?= escapar($etiquetasCampos[$campo]) ?></a>:
                <?php endif; ?>
                <?= escapar($mensaje) ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
