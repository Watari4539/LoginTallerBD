# Comprobación del paso 3

Ejecutar en la computadora donde ya funciona PHP y la conexión con Azure, después de hacer `git pull`. Conservar su `config.local.php` y la misma clave AES. No es necesario modificar la tabla existente.

## Validación automática sin conexión a Azure

Desde la raíz del proyecto:

```powershell
php tests/validacion_usuario.php
Get-ChildItem Backend,Frontend,tests -Filter *.php -Recurse | ForEach-Object { php -l $_.FullName }
```

El primer comando debe mostrar `OK: 31 comprobaciones.`. El segundo debe indicar que no hay errores de sintaxis.

Si tienes Node.js, también puedes ejecutar `node tests/validacion_formulario.cjs` para probar el envío y la validación JavaScript. Esta prueba ya se ejecutó correctamente en la computadora de desarrollo; la prueba PHP y los flujos contra Azure quedan por ejecutar en la otra computadora.

## Prueba en el navegador

Usar cuentas y datos de prueba. El registro ahora está en `Frontend/crearUsuario.php`; el enlace anterior `.html` redirige al nuevo formulario.

1. Registrar un empleado con los 15 datos. Comprobar el mensaje de éxito y entrar con esa cuenta. Probar también una contraseña de al menos 10 caracteres que tenga espacios al principio y al final: debe funcionar con esos mismos espacios.
2. Introducir dirección vacía o solo espacios, teléfono corto, usuario de dos caracteres, CURP corta, cuenta con letras, fecha de nacimiento futura, salario negativo o con más de dos decimales. El formulario debe rechazar el envío. Desactivar JavaScript y repetir para comprobar también el servidor.
3. Registrar correo o usuario duplicados. Debe aparecer el error y conservarse lo capturado, excepto la contraseña. Corregir y reenviar. Recargar después de un registro exitoso no debe repetir la inserción.
4. Entrar como administrador y agregar una cuenta desde el listado. Editar sus datos, incluidos CURP, cuenta y salario. Comprobar que aparecen correctamente al volver al listado y al abrir de nuevo la edición.
5. Editar dejando la contraseña vacía: la anterior debe seguir funcionando. Después cambiarla: la nueva debe funcionar y la anterior debe rechazarse.
6. Provocar un error o un duplicado al editar: deben conservarse los cambios escritos para poder corregirlos. Intentar quitarse el propio rol de administrador debe rechazarse.
7. Cancelar la confirmación de eliminación: la cuenta debe seguir allí. Confirmar la eliminación de una cuenta de prueba: debe desaparecer. La propia cuenta del administrador no debe poder eliminarse.
8. Entrar como empleado: solo deben aparecer empleados, sin CURP, cuenta ni salario y sin acciones de gestión. Intentar abrir `Frontend/gestionarUsuario.php` debe devolver acceso denegado.
9. Cerrar sesión e intentar abrir el listado directamente: debe regresar al login.

El CSS y el diseño visual se atenderán en el paso 4.
