// Ejecutar: node tests/validacion_formulario.cjs. No necesita navegador ni dependencias.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function formulario(valores, editar = false) {
    const campos = Object.entries(valores).map(([name, value]) => ({
        name, value, type: name === 'password' ? 'password' : 'text',
        required: !(name === 'password' && editar), error: '',
        setCustomValidity(error) { this.error = error; },
        addEventListener() {},
    }));
    let enviar;
    const form = {
        querySelectorAll: () => campos,
        addEventListener: (tipo, callback) => { if (tipo === 'submit') enviar = callback; },
        reportValidity: () => campos.every((campo) => !campo.error),
    };
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../main.js'), 'utf8'), {
        document: { querySelectorAll: () => [form] }, TextEncoder,
    });
    let bloqueado = false;
    enviar({ preventDefault: () => { bloqueado = true; } });
    return { campos, bloqueado };
}

const base = {
    nombres: 'María José', apellidos: "O'Connor", direccion: 'Calle 1', telefono: '4451234567',
    nombre_usuario: 'maria.test', password: '  clave12345  ', curp: 'aaaa000101maaaaa00',
    cuenta_bancaria: '012345678901234567', salario: '15000.50',
};
let r = formulario(base);
assert.equal(r.bloqueado, false);
assert.equal(r.campos.find((c) => c.name === 'password').value, base.password);
assert.equal(r.campos.find((c) => c.name === 'curp').value, base.curp.toUpperCase());
assert.equal(formulario({ ...base, password: '' }, true).bloqueado, false);
assert.equal(formulario({ ...base, telefono: ' 4451234567 ' }).bloqueado, false);
for (const [campo, valor] of [
    ['direccion', '   '], ['telefono', '123'], ['nombre_usuario', 'ab'],
    ['curp', 'ABC'], ['cuenta_bancaria', '012345678A'], ['salario', '-1'],
    ['salario', '1e3'], ['salario', '1.001'], ['password', 'corta'], ['password', 'ñ'.repeat(37)],
]) {
    r = formulario({ ...base, [campo]: valor });
    assert.equal(r.bloqueado, true, `Debe bloquearse ${campo}: ${valor}`);
    assert.ok(r.campos.find((c) => c.name === campo).error);
}
console.log('OK: envío, normalización, edición y rechazo de 10 valores inválidos.');
