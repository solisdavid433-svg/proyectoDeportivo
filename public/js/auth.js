// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: public/js/auth.js (MANEJADOR INTERACTIVO DE LOGIN VIA FETCH API)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

document.addEventListener('DOMContentLoaded', () => {
    const formLogin = document.getElementById('form-login');
    const btnSubmit = document.getElementById('btn-submit-login');

    if (formLogin) {
        formLogin.addEventListener('submit', async (e) => {
            e.preventDefault(); // Evitamos que la página se recargue por defecto

            // Capturamos los valores de las cajas de texto de index.php
            const emailVal = document.getElementById('email').value;
            const passwordVal = document.getElementById('password').value;

            // Bloqueamos el botón temporalmente para evitar doble pulsación en la tableta
            btnSubmit.disabled = true;
            btnSubmit.innerText = 'Verificando credenciales...';

            try {
                // Enviamos los datos asíncronamente a nuestra API en PHP
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        correo: emailVal,
                        password: passwordVal
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // ¡Login correcto! Redirigimos al módulo correspondiente
                    window.location.href = data.redirect;
                } else {
                    // Si la BD rebota las credenciales, mostramos el motivo real
                    alert('⚠️ ' + data.message);
                    btnSubmit.disabled = false;
                    btnSubmit.innerText = 'Ingresar al Sistema';
                }

            } catch (error) {
                console.error('Error en la petición Fetch:', error);
                alert('❌ Error de comunicación con el servidor. Verifique XAMPP.');
                btnSubmit.disabled = false;
                btnSubmit.innerText = 'Ingresar al Sistema';
            }
        });
    }
});