#!/usr/bin/env node

/**
 * tests/node/test-endpoints.js
 * Pruebas básicas de los endpoints del backend Unideportes.
 */

const baseUrl = 'http://localhost/unideportes-system';
const testUser = `node_test_${Date.now()}`;
const testPass = 'test123';
const testEmail = `${testUser}@example.com`;

let cookie = '';

function saveCookie(headers) {
    const setCookie = headers.get('set-cookie');
    if (!setCookie) return;
    const parts = setCookie.split(',').map((chunk) => chunk.trim());
    cookie = parts.map((part) => part.split(';')[0]).join('; ');
}

async function request(path, options = {}) {
    const url = `${baseUrl}${path}`;
    const headers = options.headers ? { ...options.headers } : {};
    if (cookie) {
        headers.Cookie = cookie;
    }

    const res = await fetch(url, { ...options, headers, redirect: 'manual' });
    const text = await res.text();
    saveCookie(res.headers);
    return { ok: res.ok, status: res.status, url: res.url, text, headers: res.headers, redirected: res.redirected };
}

async function post(path, body) {
    const params = new URLSearchParams(body);
    return request(path, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString(),
    });
}

async function main() {
    console.log('=== TEST ENDPOINTS UNIDEPORTES ===\n');

    console.log('1) Probando login admin...');
    const login = await post('/controllers/auth.php', {
        username: 'admin',
        password: 'admin123',
    });
    console.log(`   - status: ${login.status}`);
    console.log(`   - url   : ${login.url}`);
    console.log(`   - cookie: ${cookie ? '✅ capturada' : '❌ no capturada'}`);
    const loginOk = login.status === 302 || login.url.includes('/views/panel_admin.php');
    console.log(loginOk ? '   - Resultado: login admin OK' : '   - Resultado: login admin fallido');

    if (!loginOk) {
        console.error('\nERROR: El endpoint de login no responde correctamente. Revisa la URL y el servidor.');
        process.exit(1);
    }

    console.log('\n2) Creando usuario de prueba...');
    const create = await post('/models/insert_user.php', {
        name: 'Node',
        lastname: 'Tester',
        username: testUser,
        password: testPass,
        email: testEmail,
        role: 'colaborador',
    });
    console.log(`   - status: ${create.status}`);
    const created = create.ok && !create.text.includes('Error en la BD');
    console.log(created ? '   - Resultado: usuario creado' : '   - Resultado: creación fallida');

    if (!created) {
        console.error('\nERROR: No se pudo crear el usuario de prueba. Verifica la base de datos y la URL.');
        process.exit(1);
    }

    console.log('\n3) Probando login del usuario creado...');
    const loginTest = await post('/controllers/auth.php', {
        username: testUser,
        password: testPass,
    });
    console.log(`   - status: ${loginTest.status}`);
    console.log(`   - url   : ${loginTest.url}`);
    const createdLoginOk = loginTest.url.includes('/views/panel_vendedor.php') || loginTest.url.includes('/views/panel_admin.php');
    console.log(createdLoginOk ? '   - Resultado: login de usuario de prueba OK' : '   - Resultado: login fallido');

    if (!createdLoginOk) {
        console.error('\nERROR: El usuario de prueba no pudo iniciar sesión. Comprueba contraseña y sesiones.');
        process.exit(1);
    }

    console.log('\n4) Obteniendo ID del usuario de prueba desde admin_user...');
    const id = await getUserId(testUser);
    console.log(`   - ID: ${id}`);

    console.log('\n5) Eliminando usuario de prueba...');
    const del = await request(`/controllers/delete_user.php?id=${id}`);
    console.log(`   - status: ${del.status}`);
    console.log(del.ok ? '   - Resultado: usuario eliminado' : '   - Resultado: eliminación fallida');

    console.log('\n=== FIN DE PRUEBAS ===');
}

async function getUserId(username) {
    const list = await request('/views/admin_user.php');
    if (!list.ok) {
        console.error('ERROR: No se pudo cargar admin_user.php. Verifica la sesión de administrador.');
        process.exit(1);
    }
    const regex = new RegExp(`<td>(\\d+)<\\/td>\\s*<td>${username}<\\/td>`, 'i');
    const match = list.text.match(regex);
    if (!match) {
        console.error('ERROR: No se pudo obtener el ID del usuario de prueba desde admin_user.php');
        process.exit(1);
    }
    return match[1];
}

main().catch((err) => {
    console.error('ERROR INESPERADO:', err);
    process.exit(1);
});
