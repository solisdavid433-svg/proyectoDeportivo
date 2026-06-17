// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: public/js/qr.js (MANEJADOR DE CAMARA Y LECTOR QR ASÍNCRONO)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

let html5Qrcode = null;
let qrEscaneando = false;

async function toggleQRScanner() {
    const contenedor = document.getElementById('qr-reader-container');
    const boton = document.getElementById('btn-toggle-qr');

    // SI YA ESTÁ ESCANEANDO, LO APAGAMOS
    if (qrEscaneando) {
        apagarCamara();
        return;
    }

    // SI ESTÁ APAGADO, ENCENDEMOS LA CÁMARA
    contenedor.style.display = 'block';
    boton.innerText = "❌ Cerrar Cámara";
    boton.style.backgroundColor = "#DC2626"; // Cambia a rojo dinámicamente

    html5Qrcode = new Html5Qrcode("qr-reader");
    qrEscaneando = true;

    const config = {
        fps: 10,                  // Velocidad de escaneo (10 cuadros por segundo)
        qrbox: { width: 250, height: 250 } // Cuadro de enfoque visual
    };

    try {
        // En tabletas/celulares 'environment' prioriza la cámara trasera automáticamente
        await html5Qrcode.start(
            { facingMode: "environment" },
            config,
            onQrCodeSuccess,
            onQrCodeError
        );
    } catch (err) {
        console.error("Error al acceder a la cámara del dispositivo:", err);
        alert("⚠️ No se pudo acceder a la cámara. Verifique los permisos de su navegador.");
        apagarCamara();
    }
}

// GANCHO DE ÉXITO: Qué pasa cuando la cámara detecta un QR válido
function onQrCodeSuccess(decodedText, decodedResult) {
    // Sanamos el texto por si el QR trae espacios o texto extra
    const folioDetectado = decodedText.trim();

    if (folioDetectado) {
        // 1. Apagamos la cámara de inmediato para evitar lecturas duplicadas
        apagarCamara();

        // 2. Inyectamos el folio detectado en tu buscador input de staff.php
        const inputBuscar = document.getElementById('search-input');
        if (inputBuscar) {
            inputBuscar.value = folioDetectado;

            // 3. Forzamos de forma artificial el evento 'input' para que buscador.js actúe
            inputBuscar.dispatchEvent(new Event('input'));

            // 4. UX PREMIUM: Damos una milésima de segundo para que pinte la tarjeta y abrimos el modal de firma
            setTimeout(() => {
                if (typeof window.abrirFirma === 'function') {
                    window.abrirFirma(folioDetectado);
                }
            }, 300);
        }
    }
}

// GANCHO DE ERROR CONTINUO: Silenciamos los fallos de cuadros vacíos para no saturar la consola
function onQrCodeError(errorMessage) {
    // Es normal que falle si pasa un cuadro de video sin un código QR enfrente
}

function apagarCamara() {
    const contenedor = document.getElementById('qr-reader-container');
    const boton = document.getElementById('btn-toggle-qr');

    if (html5Qrcode) {
        html5Qrcode.stop().then(() => {
            html5Qrcode.clear();
            contenedor.style.display = 'none';
            boton.innerText = "📷 Escanear QR";
            boton.style.backgroundColor = "#0F172A";
            qrEscaneando = false;
        }).catch(err => {
            console.error("Error al detener la cámara:", err);
        });
    } else {
        contenedor.style.display = 'none';
        boton.innerText = "📷 Escanear QR";
        boton.style.backgroundColor = "#0F172A";
        qrEscaneando = false;
    }
}