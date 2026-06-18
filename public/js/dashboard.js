// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: public/js/dashboard.js (MANEJADOR ASÍNCRONO DE CONTROL DE METRICAS)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

document.addEventListener('DOMContentLoaded', () => {
    // Ejecutamos la carga de estadísticas de inmediato al entrar
    cargarEstadisticas();

    // Opcional: Refrescar automáticamente cada 30 segundos para ver cambios en vivo
    setInterval(cargarEstadisticas, 30000);
});

async function cargarEstadisticas() {
    try {
        const eventoId = document.getElementById('hdn-evento-id')?.value || 1;
        const response = await fetch(`api/estadisticas.php?evento_id=${eventoId}`);
        const res = await response.json();

        if (!res.success) {
            console.error("Error al recuperar analíticas:", res.message);
            return;
        }

        const r = res.resumen;
        const t = res.tallas;
        const s = res.staff;

        // 1. ACOMODAR SECCIÓN RESUMEN (Tarjetas numéricas)
        if (document.getElementById('lbl-total-atletas')) document.getElementById('lbl-total-atletas').innerText = r.total;
        if (document.getElementById('lbl-entregados')) document.getElementById('lbl-entregados').innerText = r.entregados;
        if (document.getElementById('lbl-pendientes')) document.getElementById('lbl-pendientes').innerText = r.pendientes;
        if (document.getElementById('lbl-cambios')) document.getElementById('lbl-cambios').innerText = r.cambios;

        // 2. ACTUALIZAR BARRA DE PROGRESO GENERAL
        const barraProgreso = document.getElementById('barra-progreso-global');
        const textoProgreso = document.getElementById('txt-porcentaje-global');
        if (barraProgreso && textoProgreso) {
            barraProgreso.style.width = `${r.porcentaje}%`;
            textoProgreso.innerText = `${r.porcentaje}% Completado`;
        }

        // 3. ACTUALIZAR INVENTARIO DE PLAYERAS DINÁMICO (FORMATO STOCK: ENTREGADOS / CONFIGURADOS)
        if (document.getElementById('txt-talla-xs')) document.getElementById('txt-talla-xs').innerText = `${t.XS.entregados} / ${t.XS.total}`;
        if (document.getElementById('txt-talla-ch')) document.getElementById('txt-talla-ch').innerText = `${t.CH.entregados} / ${t.CH.total}`;
        if (document.getElementById('txt-talla-m')) document.getElementById('txt-talla-m').innerText = `${t.M.entregados} / ${t.M.total}`;
        if (document.getElementById('txt-talla-g')) document.getElementById('txt-talla-g').innerText = `${t.G.entregados} / ${t.G.total}`;
        if (document.getElementById('txt-talla-xg')) document.getElementById('txt-talla-xg').innerText = `${t.XG.entregados} / ${t.XG.total}`;
        if (document.getElementById('txt-talla-2xl')) document.getElementById('txt-talla-2xl').innerText = `${t['2XL'].entregados} / ${t['2XL'].total}`;

        // 4. GENERAR TABLA/LISTA DE RENDIMIENTO DEL STAFF
        const contenedorStaff = document.getElementById('lista-rendimiento-staff');
        if (contenedorStaff) {
            contenedorStaff.innerHTML = ''; // Limpiamos tabla estática anterior

            if (s.length === 0) {
                contenedorStaff.innerHTML = '<p class="text-muted">Ningún operador ha realizado entregas aún.</p>';
                return;
            }

            s.forEach((operador, index) => {
                const fila = document.createElement('div');
                fila.className = 'staff-performance-row';
                fila.style = 'display: flex; justify-content: space-between; padding: 0.75rem; border-bottom: 1px solid #E2E8F0;';
                fila.innerHTML = `
                    <span><b>#${index + 1}</b> ${operador.operador}</span>
                    <span class="badge-count" style="background-color: #EFF6FF; color: #1E40AF; padding: 0.2rem 0.7rem; border-radius: 20px; font-weight: 700;">
                        ${operador.total_entregas} kits
                    </span>
                `;
                contenedorStaff.appendChild(fila);
            });
        }

    } catch (error) {
        console.error("Error crítico en la comunicación del Dashboard:", error);
    }
}