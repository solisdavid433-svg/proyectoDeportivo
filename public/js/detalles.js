// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: public/js/detalles.js (CONTROLADOR DE VISTA DE HISTORIAL DE ENTREGA)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

const modalDetalles = document.getElementById('modal-detalles');

window.verDetalles = async (folio) => {
    try {
        const response = await fetch(`api/detalles.php?folio=${folio}`);
        const res = await response.json();

        if (res.success) {
            const d = res.data;

            // Inyectamos la información recuperada de SQL Server en el HTML
            document.getElementById('det-folio').innerText = d.folio;
            document.getElementById('det-atleta').innerText = d.atleta;
            document.getElementById('det-categoria').innerText = d.categoria;
            document.getElementById('det-talla').innerText = d.talla_playera;
            document.getElementById('det-fecha').innerText = d.fecha_entrega;
            document.getElementById('det-staff').innerText = d.nombre_staff; // Muestra quién lo atendió

            // Pintamos la imagen de la firma Base64 guardada
            document.getElementById('det-firma-img').src = d.firma_base64;

            // Desplegamos el modal centrado
            if (modalDetalles) modalDetalles.style.display = 'flex';
        } else {
            alert("⚠️ " + res.message);
        }
    } catch (error) {
        console.error(error);
        alert("❌ Error al recuperar los detalles de la entrega.");
    }
};

window.cerrarDetalles = () => {
    if (modalDetalles) modalDetalles.style.display = 'none';
};

// Función para mostrar el select oculto de cambio de talla
window.mostrarSeccionCambio = () => {
    document.getElementById('seccion-cambio-talla').style.display = 'block';
    document.getElementById('btn-activar-cambio').style.display = 'none';
};

// Asegúrate de resetear la vista al cerrar el modal
const funcionCerrarOriginal = window.cerrarDetalles;
window.cerrarDetalles = () => {
    document.getElementById('seccion-cambio-talla').style.display = 'none';
    document.getElementById('btn-activar-cambio').style.display = 'inline-block';
    if (typeof funcionCerrarOriginal === 'function') funcionCerrarOriginal();
};

// Petición asíncrona para guardar el cambio en SQL Server
window.procesarCambioTalla = async () => {
    const folio = document.getElementById('det-folio').innerText;
    const nuevaTalla = document.getElementById('det-nueva-talla').value;

    try {
        const response = await fetch('api/cambiar_talla.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                folio: folio,
                talla: nuevaTalla
            })
        });

        const res = await response.json();

        if (res.success) {
            alert("✔️ Cambio de talla registrado con éxito.");
            cerrarDetalles();
            // Refrescamos el buscador de inmediato para ver el nuevo badge de "CAMBIO"
            document.getElementById('search-input').dispatchEvent(new Event('input'));
        } else {
            alert("❌ Error: " + res.message);
        }
    } catch (error) {
        console.error(error);
        alert("❌ Error de comunicación con el servidor.");
    }
};