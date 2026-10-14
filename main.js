// La validación del navegador ayuda al usuario; el servidor vuelve a validar todo.
document.querySelectorAll('[data-formulario-usuario]').forEach((form) => {
    const validarCampo = (campo) => {
        campo.setCustomValidity('');
        if (!campo.name || campo.type === 'hidden' || campo.type === 'submit') return;
        const valor = campo.name === 'password' ? campo.value : campo.value.trim();
        let error = '';
        if (campo.required && !valor) error = 'Completa este campo.';
        if (valor) {
            switch (campo.name) {
                case 'nombres':
                case 'apellidos':
                    if (!/^[\p{L}\p{M} .'-]+$/u.test(valor)) {
                        error = 'Usa letras, espacios, puntos, apóstrofes o guiones.';
                    }
                    break;
                case 'telefono':
                    if (!/^[0-9]{10}$/.test(valor)) error = 'El teléfono debe tener 10 dígitos.';
                    break;
                case 'nombre_usuario':
                    if (!/^[a-zA-Z0-9_.-]{3,50}$/.test(valor)) error = 'Usa entre 3 y 50 letras, números, puntos o guiones.';
                    break;
                case 'curp':
                    if (!/^[A-Z0-9]{18}$/i.test(valor)) error = 'La CURP debe tener 18 caracteres alfanuméricos.';
                    break;
                case 'cuenta_bancaria':
                    if (!/^[0-9]{10,18}$/.test(valor)) error = 'La cuenta debe tener entre 10 y 18 dígitos.';
                    break;
                case 'password':
                    if (Array.from(valor).length < 10 || new TextEncoder().encode(valor).length > 72 || valor.includes('\0')) {
                        error = 'Usa al menos 10 caracteres y máximo 72 bytes; no incluyas caracteres nulos.';
                    }
                    break;
                case 'salario':
                    if (!/^[0-9]+(?:\.[0-9]{1,2})?$/.test(valor) || Number(valor) > 9999999999.99) {
                        error = 'Usa un salario entre 0 y 9999999999.99, con hasta dos decimales.';
                    }
                    break;
            }
        }
        campo.setCustomValidity(error);
    };
    const campos = Array.from(form.querySelectorAll('input, select'));
    // Se usa reportValidity después de las reglas propias, también para envíos con Enter.
    form.noValidate = true;
    campos.forEach((campo) => {
        campo.addEventListener('input', () => validarCampo(campo));
        campo.addEventListener('change', () => validarCampo(campo));
    });
    form.addEventListener('submit', (event) => {
        campos.forEach((campo) => {
            if (campo.type !== 'hidden' && campo.name !== 'password') {
                campo.value = campo.value.trim();
                if (campo.name === 'curp') campo.value = campo.value.toUpperCase();
            }
        });
        campos.forEach(validarCampo);
        if (!form.reportValidity()) event.preventDefault();
    });
});
