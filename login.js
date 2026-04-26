// login.js — conecta loginForm y registerForm con la API
document.addEventListener('DOMContentLoaded', function () {

    const API_URL       = `${window.location.origin}/api/coneccion.php`;
    const DASHBOARD_URL = `${window.location.origin}/Dashboard/dist/index2.html`;

    /* ══ REGISTRO ══════════════════════════════════════════ */
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const nombre           = document.getElementById('registerName').value.trim();
            const apellido_paterno = document.getElementById('registerApellidoPaterno').value.trim();
            const apellido_materno = document.getElementById('registerApellidoMaterno').value.trim();
            const email            = document.getElementById('registerEmail').value.trim();
            const password         = document.getElementById('registerPassword').value;
            const confirmPassword  = document.getElementById('registerConfirmPassword').value;
            const acceptTerms      = document.getElementById('acceptTerms').checked;

            if (!nombre || !apellido_paterno || !apellido_materno || !email || !password || !confirmPassword) {
                showMessage('register', 'Todos los campos son obligatorios.', 'error');
                return;
            }
            if (password.length < 8) {
                showMessage('register', 'La contraseña debe tener mínimo 8 caracteres.', 'error');
                return;
            }
            if (password !== confirmPassword) {
                showMessage('register', 'Las contraseñas no coinciden.', 'error');
                return;
            }
            if (!acceptTerms) {
                showMessage('register', 'Debes aceptar los términos y condiciones.', 'error');
                return;
            }

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        register: true,
                        nombre,
                        apellido_paterno,
                        apellido_materno,
                        correo: email,
                        contrasena: password
                    })
                });

                const text = await response.text();
                let data = null;
                try { data = text ? JSON.parse(text) : null; } catch (err) {
                    showMessage('register', 'Error del servidor: respuesta inválida.', 'error');
                    return;
                }

                if (response.status === 409) {
                    showMessage('register', (data && data.error) ? data.error : 'Este correo ya está en uso.', 'error');
                    return;
                }
                if (response.ok && data && data.success) {
                    showMessage('register', 'Registro exitoso. Ahora puedes iniciar sesión.', 'success');
                    registerForm.reset();
                    // Cambia a la pestaña de login tras 1.5s
                    setTimeout(() => {
                        document.querySelector('.auth-tab[data-tab="login"]').click();
                    }, 1500);
                } else {
                    showMessage('register', (data && data.error) ? data.error : 'Error al registrar usuario.', 'error');
                }
            } catch (err) {
                showMessage('register', 'Error de red: ' + err.message, 'error');
            }
        });
    }

    /* ══ LOGIN ══════════════════════════════════════════════ */
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const email    = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;

            if (!email || !password) {
                showMessage('login', 'Todos los campos son obligatorios.', 'error');
                return;
            }
            if (!/^[\w\.\-]+@[\w\.\-]+\.\w{2,}$/.test(email)) {
                showMessage('login', 'Correo electrónico no válido.', 'error');
                return;
            }

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        login: true,
                        correo: email,
                        contrasena: password
                    })
                });

                const text = await response.text();
                let data = null;
                try { data = text ? JSON.parse(text) : null; } catch (err) {
                    showMessage('login', 'Error del servidor: respuesta inválida.', 'error');
                    return;
                }

                if (response.ok && data && data.success) {
                    if (data.nombre)     localStorage.setItem('nombre_usuario', data.nombre);
                    if (data.id_usuario) localStorage.setItem('id_usuario', data.id_usuario);

                    // Buscar dispositivo del usuario
                    if (data.id_usuario) {
                        try {
                            const deviceRes  = await fetch(API_URL, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ check_device: true, id_usuario: data.id_usuario })
                            });
                            const deviceData = await deviceRes.json();
                            if (deviceData.success && deviceData.hasDevice) {
                                const idDispositivo = deviceData.device.id_dispositivo;
                                const tipo = (deviceData.device.tipo_monitoreo || '').toLowerCase();
                                const modo = tipo.includes('sueno') || tipo.includes('sueño') ? 'sueno' : 'caidas';
                                localStorage.setItem('id_dispositivo', idDispositivo);
                                localStorage.setItem('monitoring_mode', modo);
                            }
                        } catch (err) {
                            console.warn('No se pudo obtener el dispositivo:', err);
                        }
                    }

                    showMessage('login', '¡Bienvenido! Redirigiendo...', 'success');
                    setTimeout(() => { window.location.href = DASHBOARD_URL; }, 800);

                } else {
                    showMessage('login', (data && data.error) ? data.error : 'Correo o contraseña incorrectos.', 'error');
                }
            } catch (err) {
                showMessage('login', 'Error de red: ' + err.message, 'error');
            }
        });
    }

    /* ══ HELPER: muestra mensaje inline en vez de alert() ══ */
    function showMessage(section, text, type) {
        const containerId = 'msg-' + section;
        let el = document.getElementById(containerId);
        if (!el) {
            el = document.createElement('div');
            el.id = containerId;
            el.style.cssText = `
                padding: 10px 14px;
                border-radius: 4px;
                font-size: 13px;
                margin-bottom: 14px;
                font-family: 'Space Grotesk', sans-serif;
            `;
            // Inserta al inicio del formulario correspondiente
            const form = document.getElementById(section === 'login' ? 'loginForm' : 'registerForm');
            if (form) form.insertBefore(el, form.firstChild);
        }
        el.textContent = text;
        el.style.background = type === 'error' ? '#fdecea' : '#e6f4ea';
        el.style.color      = type === 'error' ? '#b3261e' : '#1e6b3a';
        el.style.border     = type === 'error' ? '1px solid #f5c6c3' : '1px solid #b7dfbf';
        // Desaparece solo después de 4 segundos
        clearTimeout(el._timer);
        el._timer = setTimeout(() => { el.textContent = ''; el.style.background = 'transparent'; el.style.border = 'none'; }, 4000);
    }

});