// Espera a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    // URL base para todas las peticiones (usar absoluta para evitar problemas de ruta)
    const API_URL = `${window.location.origin}/api/coneccion.php`;
    const DASHBOARD_URL = `${window.location.origin}/Dashboard/dist/index2.html`;
    
    // Registro de usuario
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const nombre = document.getElementById('registerName').value.trim();
            const apellido_paterno = document.getElementById('registerApellidoPaterno').value.trim();
            const apellido_materno = document.getElementById('registerApellidoMaterno').value.trim();
            const email = document.getElementById('registerEmail').value.trim();
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('registerConfirmPassword').value;
            const acceptTerms = document.getElementById('acceptTerms').checked;

            // Validaciones...
            if (!nombre || !apellido_paterno || !apellido_materno || !email || !password || !confirmPassword) {
                alert('Todos los campos son obligatorios.');
                return;
            }
            // ... resto de validaciones

            try {
                console.log('Enviando registro a:', API_URL);
                const response = await fetch(API_URL, {  // CAMBIADO
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
                
                console.log('Status registro:', response.status);
                const text = await response.text();
                console.log('Respuesta registro:', text);
                
                let data = null;
                try { 
                    data = text ? JSON.parse(text) : null; 
                } catch (e) { 
                    console.error('Error parsing JSON:', e);
                    alert('Error del servidor: respuesta inválida');
                    return;
                }
                
                if (response.status === 409) {
                    alert((data && data.error) ? data.error : 'Este correo ya esta en uso.');
                    return;
                }
                if (response.ok && data && data.success) {
                    alert('Registro exitoso. Ahora puedes iniciar sesión.');
                    // Cambia a la pestaña de login
                    document.querySelector('.auth-tab[data-tab="login"]').click();
                    registerForm.reset();
                } else {
                    alert((data && data.error) ? data.error : 'Error al registrar usuario.');
                }
            } catch (err) {
                console.error('Error de red:', err);
                alert('Error de red: ' + err.message);
            }
        });
    }

    // Inicio de sesión
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const email = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;

            // Validaciones...
            if (!email || !password) {
                alert('Todos los campos son obligatorios.');
                return;
            }
            if (!/^[\w\.-]+@[\w\.-]+\.\w{2,}$/.test(email)) {
                alert('Correo electrónico no válido.');
                return;
            }

            try {
                console.log('Enviando login a:', API_URL);
                const response = await fetch(API_URL, {  // CAMBIADO
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        login: true,
                        correo: email,
                        contrasena: password
                    })
                });
                
                console.log('Status login:', response.status);
                const text = await response.text();
                console.log('Respuesta login completa:', text);
                
                let data = null;
                try { 
                    data = text ? JSON.parse(text) : null; 
                } catch (e) { 
                    console.error('Error parsing JSON:', e);
                    alert('Error del servidor: respuesta inválida');
                    return;
                }
                
                if (response.ok && data && data.success) {
                    console.log('Login exitoso, usuario:', data);
                    
                    // Guarda el nombre en localStorage
                    if (data.nombre) {
                        localStorage.setItem('nombre_usuario', data.nombre);
                        console.log('Nombre guardado:', data.nombre);
                    }
                    // Guarda el id del usuario
                    if (data.id_usuario) {
                        localStorage.setItem('id_usuario', data.id_usuario);
                        console.log('ID usuario guardado:', data.id_usuario);

                        // Buscar y guardar el dispositivo del usuario
                        try {
                            const deviceRes = await fetch(API_URL, {
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
                                console.log('Dispositivo guardado:', { idDispositivo, modo });
                            }
                        } catch (err) {
                            console.warn('No se pudo obtener el dispositivo del usuario:', err);
                        }
                    }
                    
                    // Redirige al dashboard (ligero delay para asegurar guardado)
                    setTimeout(() => {
                        window.location.href = DASHBOARD_URL;
                    }, 300);
                } else {
                    const errorMsg = (data && data.error) ? data.error : 'Correo o contraseña incorrectos.';
                    console.error('Error login:', errorMsg);
                    alert(errorMsg);
                }
            } catch (err) {
                console.error('Fetch error:', err);
                alert('Error de red: ' + err.message);
            }
        });
    }

    // ... resto del código para cambiar pestañas
});
    // Cambiar entre pestañas login/registro
    document.querySelectorAll('.auth-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const tabName = this.getAttribute('data-tab');
            document.querySelectorAll('.auth-content').forEach(c => c.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
        });
    });
    // Cambiar entre login/registro desde los enlaces de abajo
    document.querySelectorAll('.auth-switch a').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const tabName = this.getAttribute('data-tab');
            if (tabName) {
                document.querySelector(`.auth-tab[data-tab="${tabName}"]`).click();
            }
        });
    });