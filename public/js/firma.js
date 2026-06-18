// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: public/js/firma.js (VERSIÓN CORREGIDA Y BLINDADA)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

let folioActual = null;
let modal = null;
let canvas = null;
let ctx = null;
let dibujando = false;

// 1. Esperamos a que todo el HTML esté cargado antes de buscar los elementos
document.addEventListener('DOMContentLoaded', () => {
    modal = document.getElementById('modal-firma');
    canvas = document.getElementById('canvas-firma');

    // Control de seguridad: Si no encuentra el canvas, avisa pero NO rompe el archivo
    if (!canvas || !modal) {
        console.error("Error: No se encontró el modal o el canvas en el HTML. Revisa los IDs.");
        return;
    }

    ctx = canvas.getContext('2d');

    // Configuración del trazo del lápiz
    ctx.strokeStyle = "#000000";
    ctx.lineWidth = 3;
    ctx.lineCap = "round";

    // Eventos de Mouse para PC
    canvas.addEventListener('mousedown', iniciarDibujo);
    canvas.addEventListener('mousemove', dibujar);
    canvas.addEventListener('mouseup', detenerDibujo);

    // Eventos Táctiles para Tabletas / Celulares
    canvas.addEventListener('touchstart', iniciarDibujo);
    canvas.addEventListener('touchmove', dibujar);
    canvas.addEventListener('touchend', detenerDibujo);

    // Asignamos eventos a los botones del modal de forma segura
    document.getElementById('btn-limpiar').addEventListener('click', limpiarCanvas);
    document.getElementById('btn-guardar-entrega').addEventListener('click', procesarEntrega);
});

// 2. REGISTRO GLOBAL: Estas funciones quedan libres desde el inicio para el "onclick" del buscador
window.abrirFirma = (folio) => {
    folioActual = folio;

    const txtFolio = document.getElementById('modal-folio-txt');
    const selectTalla = document.getElementById('modal-talla-select');

    if (txtFolio) txtFolio.innerText = folio;
    if (selectTalla) selectTalla.value = ""; // Limpiamos la selección anterior

    if (modal) {
        modal.style.display = 'flex'; // Abrimos el modal visualmente
    }

    // Damos una milésima de segundo para limpiar el lienzo de firmas viejo
    setTimeout(limpiarCanvas, 50);
};

window.cerrarFirma = () => {
    if (modal) modal.style.display = 'none';
    folioActual = null;
};

// --- LÓGICA INTERNA DEL DIBUJO ---
function obtenerPosicion(e) {
    const rect = canvas.getBoundingClientRect();
    const clienteX = e.touches ? e.touches[0].clientX : e.clientX;
    const clienteY = e.touches ? e.touches[0].clientY : e.clientY;
    return {
        x: clienteX - rect.left,
        y: clienteY - rect.top
    };
}

function iniciarDibujo(e) {
    if (!ctx) return;
    dibujando = true;
    const pos = obtenerPosicion(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
    e.preventDefault();
}

function dibujar(e) {
    if (!dibujando || !ctx) return;
    const pos = obtenerPosicion(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    e.preventDefault();
}

function detenerDibujo() {
    dibujando = false;
}

function limpiarCanvas() {
    if (ctx && canvas) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
}

// --- ENVÍO ASÍNCRONO A LA API ---
async function procesarEntrega() {
    const firmaBase64 = canvas.toDataURL();
    const tallaSeleccionada = document.getElementById('modal-talla-select').value;

    if (!tallaSeleccionada) {
        alert("Por favor, seleccione la talla de playera que se va a entregar.");
        return;
    }

    if (firmaBase64.length < 2000) {
        alert("Por favor, el competidor debe firmar para recibir su kit.");
        return;
    }

    try {
        const response = await fetch('api/entregar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                folio: folioActual,
                firma: firmaBase64,
                talla: tallaSeleccionada,
                evento_id: 1
            })
        });

        const data = await response.json();

        if (data.success) {
            alert("¡Kit y playera registrados con éxito!");
            cerrarFirma();
            // Refrescamos el buscador automáticamente
            document.getElementById('search-input').dispatchEvent(new Event('input'));
        } else {
            alert("Error: " + data.message);
        }
    } catch (error) {
        console.error(error);
        alert("Error de comunicación con el servidor.");
    }
}