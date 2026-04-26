/*<script>
async function handleSubmit() {
    const emailEl = document.getElementById('recoveryEmail');
    const email = emailEl.value.trim();

    // Validación básica
    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

    if (!valid) {
        emailEl.classList.add('error');
        emailEl.focus();
        setTimeout(() => emailEl.classList.remove('error'), 2500);
        return;
   

    // Elementos UI
    const btn = document.getElementById('submitBtn');
    const spinner = document.getElementById('btnSpinner');
    const icon = document.getElementById('btnIcon');
    const text = document.getElementById('btnText');

    // Estado cargando
    btn.disabled = true;
    spinner.style.display = 'block';
    icon.style.display = 'none';
    text.textContent = 'Enviando…';

    try {
        //  LLAMADA REAL AL BACKEND
        const response = await fetch('/api/recuperar-contrasena', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Error al enviar correo');
        }

        // ✅ ÉXITO
        document.getElementById('confirmedEmail').textContent = email;
        document.getElementById('formState').style.display = 'none';
        document.getElementById('successState').style.display = 'block';

    } catch (error) {
        console.error(error);

        alert('Error al enviar el correo. Intenta de nuevo.');

        // Restaurar botón
        btn.disabled = false;
        spinner.style.display = 'none';
        icon.style.display = '';
        text.textContent = 'Enviar enlace de recuperación';
    }
}

// Reset
function resetForm() {
    document.getElementById('successState').style.display = 'none';
    document.getElementById('formState').style.display = 'block';
    document.getElementById('recoveryEmail').value = '';

    const btn = document.getElementById('submitBtn');
    btn.disabled = false;
    document.getElementById('btnSpinner').style.display = 'none';
    document.getElementById('btnIcon').style.display = '';
    document.getElementById('btnText').textContent = 'Enviar enlace de recuperación';
}

// Enter para enviar
document.getElementById('recoveryEmail').addEventListener('keydown', e => {
    if (e.key === 'Enter') handleSubmit();
});
</script>
*/