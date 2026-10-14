    <fieldset>
        <legend>Datos personales</legend>
        <div class="form-grid">
        <div class="form-group">
        <label for="nombres">Nombres</label>
        <input id="nombres" name="nombres" maxlength="100" value="<?= escapar($fila['nombres']) ?>" required>
        </div>
        <div class="form-group">
        <label for="apellidos">Apellidos</label>
        <input id="apellidos" name="apellidos" maxlength="120" value="<?= escapar($fila['apellidos']) ?>" required>
        </div>
        <div class="form-group">
        <label for="fecha_nacimiento">Fecha de nacimiento</label>
        <input id="fecha_nacimiento" max="<?= date('Y-m-d') ?>" type="date" name="fecha_nacimiento" value="<?= escapar($fila['fecha_nacimiento']) ?>" required>
        </div>
        <div class="form-group">
        <label for="genero">Género</label>
        <select id="genero" name="genero" required>
            <option value="">Seleccione...</option>
            <option value="M" <?= $fila['genero'] === 'M' ? 'selected' : '' ?>>Masculino</option>
            <option value="F" <?= $fila['genero'] === 'F' ? 'selected' : '' ?>>Femenino</option>
            <option value="O" <?= $fila['genero'] === 'O' ? 'selected' : '' ?>>Otro</option>
        </select>
        </div>
        <div class="form-group">
        <label for="estado_civil">Estado civil</label>
        <select id="estado_civil" name="estado_civil" required>
            <option value="">Seleccione...</option>
            <option value="soltero" <?= $fila['estado_civil'] === 'soltero' ? 'selected' : '' ?>>Soltero/a</option>
            <option value="casado" <?= $fila['estado_civil'] === 'casado' ? 'selected' : '' ?>>Casado/a</option>
            <option value="divorciado" <?= $fila['estado_civil'] === 'divorciado' ? 'selected' : '' ?>>Divorciado/a</option>
        </select>
        </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Contacto</legend>
        <div class="form-grid">
        <div class="form-group">
        <label for="email">Correo</label>
        <input id="email" type="email" name="email" maxlength="254" value="<?= escapar($fila['email']) ?>" required>
        </div>
        <div class="form-group">
        <label for="telefono">Teléfono (10 dígitos)</label>
        <input id="telefono" type="tel" name="telefono" pattern="[0-9]{10}" inputmode="numeric" maxlength="10" value="<?= escapar($fila['telefono']) ?>" required>
        </div>
        <div class="form-group">
        <label for="direccion">Dirección</label>
        <input id="direccion" name="direccion" maxlength="255" value="<?= escapar($fila['direccion']) ?>" required>
        </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Acceso y contratación</legend>
        <div class="form-grid">
        <?php if ($administrativo): ?>
        <div class="form-group">
        <label for="rol">Rol</label>
        <select id="rol" name="rol" required>
            <option value="empleado" <?= $fila['rol'] === 'empleado' ? 'selected' : '' ?>>Empleado</option>
            <option value="administrador" <?= $fila['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
        </select>
        </div>
        <?php else: ?>
        <p class="help-text grid-wide">Tu cuenta tendrá acceso de consulta como empleado.</p>
        <?php endif; ?>
        <div class="form-group">
        <label for="fecha_contratacion">Fecha de contratación</label>
        <input id="fecha_contratacion" type="date" name="fecha_contratacion" value="<?= escapar($fila['fecha_contratacion']) ?>" required>
        </div>
        <div class="form-group">
        <label for="nombre_usuario">Nombre de usuario</label>
        <input id="nombre_usuario" name="nombre_usuario" minlength="3" maxlength="50" pattern="[a-zA-Z0-9_.\-]{3,50}" value="<?= escapar($fila['nombre_usuario']) ?>" required>
        </div>
        <div class="form-group">
        <label for="password">Contraseña <?= !$editar ? '' : '(deja en blanco para conservarla)' ?></label>
        <input id="password" aria-describedby="password-ayuda" type="password" name="password" minlength="10" maxlength="72" autocomplete="new-password" <?= !$editar ? 'required' : '' ?>>
        <p id="password-ayuda" class="help-text">Mínimo 10 caracteres y máximo 72 bytes. Los acentos y símbolos pueden ocupar más de un byte.</p>
        </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Datos protegidos</legend>
        <div class="form-grid">
        <div class="form-group">
        <label for="curp">CURP</label>
        <input id="curp" name="curp" pattern="[A-Za-z0-9]{18}" minlength="18" maxlength="18" value="<?= escapar($fila['curp']) ?>" required>
        </div>
        <div class="form-group">
        <label for="cuenta_bancaria">Cuenta bancaria o CLABE</label>
        <input id="cuenta_bancaria" name="cuenta_bancaria" inputmode="numeric" pattern="[0-9]{10,18}" minlength="10" maxlength="18" value="<?= escapar($fila['cuenta_bancaria']) ?>" required>
        </div>
        <div class="form-group">
        <label for="salario">Salario mensual</label>
        <input id="salario" type="number" name="salario" min="0" max="9999999999.99" step="0.01" value="<?= escapar($fila['salario']) ?>" required>
        </div>
        </div>
    </fieldset>
