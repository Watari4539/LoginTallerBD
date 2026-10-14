<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Backend/cargar_usuarios.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
<header class="site-header">
    <div class="header-content">
        <a class="brand" href="usuarios.php">Gestión de usuarios</a>
        <div class="session-info">
            <span class="session-name"><?= escapar($usuario['nombre_usuario']) ?></span>
            <span class="badge"><?= $esAdmin ? 'Administrador' : 'Empleado' ?></span>
            <form action="../Backend/logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
                <button class="button-secondary" type="submit">Cerrar sesión</button>
            </form>
        </div>
    </div>
</header>
<main class="page">
    <div class="page-heading">
        <h1><?= $esAdmin ? 'Usuarios' : 'Empleados' ?></h1>
        <p class="muted"><?= $esAdmin ? 'Consulta los datos y administra las cuentas del sistema.' : 'Consulta los datos de contacto de los empleados.' ?></p>
    </div>

    <?php if (isset($mensajes[$estado])): ?>
        <p class="alert alert-success" role="status"><?= escapar($mensajes[$estado]) ?></p>
    <?php endif; ?>
    <?php if ($errorListado): ?>
        <p class="alert alert-error" role="alert">No se pudo cargar la lista de usuarios. Revisa la conexión con Azure.</p>
    <?php endif; ?>

    <?php if ($esAdmin): ?>
        <section class="card" aria-labelledby="titulo-listado">
            <div class="table-toolbar">
                <div><h2 id="titulo-listado">Usuarios registrados</h2><p class="muted"><?= $errorListado ? 'Listado no disponible' : count($usuarios) . ' cuentas registradas' ?></p></div>
                <a class="button" href="gestionarUsuario.php">Agregar usuario</a>
            </div>
        <div class="table-wrap" role="region" aria-labelledby="titulo-listado" tabindex="0">
            <table class="table-admin">
                <caption class="sr-only">Usuarios y datos protegidos. Vista de administrador.</caption>
                <thead><tr>
                    <th scope="col">ID</th><th scope="col">Nombre</th><th scope="col">Usuario</th><th scope="col">Rol</th><th scope="col">Correo</th><th scope="col">Teléfono</th>
                    <th scope="col">Fecha nacimiento</th><th scope="col">Género</th><th scope="col">Estado civil</th><th scope="col">Dirección</th>
                    <th scope="col">Contratación</th><th scope="col">CURP</th><th scope="col">Cuenta bancaria</th><th scope="col">Salario</th><th scope="col">Acciones</th>
                </tr></thead>
                <tbody>
                <?php if (!$usuarios && !$errorListado): ?>
                    <tr><td class="empty-state" colspan="<?= $esAdmin ? 15 : 5 ?>">No hay usuarios para mostrar.</td></tr>
                <?php endif; ?>
                <?php foreach ($usuarios as $fila): ?>
                    <tr>
                        <td><?= escapar($fila['id']) ?></td>
                        <td><?= escapar($fila['nombres'] . ' ' . $fila['apellidos']) ?></td>
                        <td><?= escapar($fila['nombre_usuario']) ?></td>
                        <td><?= escapar($fila['rol']) ?></td>
                        <td><?= escapar($fila['email']) ?></td>
                        <td><?= escapar($fila['telefono']) ?></td>
                        <td><?= escapar($fila['fecha_nacimiento']) ?></td>
                        <td><?= escapar($fila['genero']) ?></td>
                        <td><?= escapar($fila['estado_civil']) ?></td>
                        <td><?= escapar($fila['direccion']) ?></td>
                        <td><?= escapar($fila['fecha_contratacion']) ?></td>
                        <td><?= escapar($fila['curp']) ?></td>
                        <td><?= escapar($fila['cuenta_bancaria']) ?></td>
                        <td><?= escapar($fila['salario']) ?></td>
                        <td>
                            <div class="row-actions">
                            <a class="button button-secondary" href="gestionarUsuario.php?id=<?= escapar($fila['id']) ?>">Editar</a>
                            <form action="../Backend/gestionar_usuario.php" method="post" onsubmit="return confirm('¿Eliminar este usuario?');">
                                <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= escapar($fila['id']) ?>">
                                <button class="button-danger" type="submit" <?= (int) $fila['id'] === (int) $usuario['id'] ? 'disabled title="No puedes eliminar tu propia cuenta"' : '' ?>>Eliminar</button>
                            </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="table-note">Desplaza la tabla horizontalmente para consultar todas las columnas.</p>
        </section>
    <?php else: ?>
        <section class="card" aria-labelledby="titulo-listado">
            <div class="table-toolbar">
                <div><h2 id="titulo-listado">Directorio de empleados</h2><p class="muted"><?= $errorListado ? 'Listado no disponible' : count($usuarios) . ' empleados' ?> · Solo lectura</p></div>
            </div>
        <div class="table-wrap" role="region" aria-labelledby="titulo-listado" tabindex="0">
            <table class="table-employee">
                <caption class="sr-only">Empleados y datos de contacto. Vista de solo lectura.</caption>
                <thead><tr><th scope="col">Nombre</th><th scope="col">Usuario</th><th scope="col">Correo</th><th scope="col">Teléfono</th><th scope="col">Contratación</th></tr></thead>
                <tbody>
                <?php if (!$usuarios && !$errorListado): ?>
                    <tr><td class="empty-state" colspan="<?= $esAdmin ? 15 : 5 ?>">No hay usuarios para mostrar.</td></tr>
                <?php endif; ?>
                <?php foreach ($usuarios as $fila): ?>
                    <tr>
                        <td><?= escapar($fila['nombres'] . ' ' . $fila['apellidos']) ?></td>
                        <td><?= escapar($fila['nombre_usuario']) ?></td>
                        <td><?= escapar($fila['email']) ?></td>
                        <td><?= escapar($fila['telefono']) ?></td>
                        <td><?= escapar($fila['fecha_contratacion']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="table-note">Desplaza la tabla horizontalmente para consultar todas las columnas.</p>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/_piePagina.php'; ?>
</body>
</html>
