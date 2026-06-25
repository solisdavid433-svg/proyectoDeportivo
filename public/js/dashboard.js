// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: public/js/dashboard.js (MANEJADOR ASÍNCRONO MULTI-MÓDULO PERFECTO)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

document.addEventListener('DOMContentLoaded', () => {
    // Ejecutamos la carga de estadísticas de inmediato al entrar
    cargarEstadisticas();

    // Opcional: Refrescar automáticamente cada 30 segundos para ver cambios en vivo
    setInterval(cargarEstadisticas, 30000);

    // 💥 ENLACE DEL NUEVO LOCALIZADOR DE COMPETIDORES SEGMENTADO
    const inputBuscar = document.getElementById('search-admin-atleta-evento');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', async (e) => {
            const valor = e.target.value.trim();
            const tbody = document.getElementById('tabla-admin-competidores-body');
            if (!tbody) return;

            // Si limpia el buscador o escribe menos de 2 caracteres, restauramos el mensaje neutro
            if (valor.length < 2) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted" style="padding: 2rem;">
                            Escriba el nombre o folio en el buscador superior para consultar el estatus.
                        </td>
                    </tr>`;
                return;
            }

            // Capturamos de forma idéntica el ID de la carrera que está en pantalla
            const eventoId = document.getElementById('hdn-evento-id')?.value || 1;

            try {
                // Viajamos a tu api/buscar.php mandando el término y el candado de la carrera actual
                const response = await fetch(`api/buscar.php?termino=${encodeURIComponent(valor)}&evento_id=${eventoId}`);
                const atletas = await response.json();

                tbody.innerHTML = ''; // Limpiamos el estado anterior

                if (!atletas || atletas.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger" style="padding: 1.5rem;">❌ No se encontraron competidores en esta carrera bajo ese término.</td></tr>';
                    return;
                }

                // Renderizamos los primeros 10 resultados encontrados para mantener máxima velocidad
                atletas.slice(0, 10).forEach(atleta => {
                    const estatus = atleta.estatus_entrega.toUpperCase();

                    // Sincronización cromática de badges idéntica a la identidad gráfica
                    const badgeClass = (estatus === 'ENTREGADO')
                        ? "background-color: #D1FAE5; color: #065F46; font-weight: bold; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;"
                        : "background-color: #F3F4F6; color: #374151; font-weight: bold; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;";

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><span style="background-color: #EFF6FF; color: #1E40AF; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: bold; font-size: 0.85rem;">#${atleta.folio}</span></td>
                        <td><b style="color: #0F172A;">${atleta.nombre}</b></td>
                        <td><span style="color: #475569; font-size: 0.9rem;">${atleta.categoria}</span></td>
                        <td><span style="${badgeClass}">${estatus}</span></td>
                    `;
                    tbody.appendChild(tr);
                });

            } catch (error) {
                console.error("Error en el localizador analítico de competidores:", error);
            }
        });
    }
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

        // 3. ACTUALIZAR INVENTARIO DE PLAYERAS DINÁMICO
        if (document.getElementById('txt-talla-xs')) document.getElementById('txt-talla-xs').innerText = `${t.XS.entregados} / ${t.XS.total}`;
        if (document.getElementById('txt-talla-ch')) document.getElementById('txt-talla-ch').innerText = `${t.CH.entregados} / ${t.CH.total}`;
        if (document.getElementById('txt-talla-m')) document.getElementById('txt-talla-m').innerText = `${t.M.entregados} / ${t.M.total}`;
        if (document.getElementById('txt-talla-g')) document.getElementById('txt-talla-g').innerText = `${t.G.entregados} / ${t.G.total}`;
        if (document.getElementById('txt-talla-xg')) document.getElementById('txt-talla-xg').innerText = `${t.XG.entregados} / ${t.XG.total}`;
        if (document.getElementById('txt-talla-2xl')) document.getElementById('txt-talla-2xl').innerText = `${t['2XL'].entregados} / ${t['2XL'].total}`;

        // 4. GENERAR TABLA/LISTA DE RENDIMIENTO DEL STAFF
        const contenedorStaff = document.getElementById('lista-rendimiento-staff');
        if (contenedorStaff) {
            contenedorStaff.innerHTML = '';

            if (s.length === 0) {
                contenedorStaff.innerHTML = '<p class="text-muted" style="padding: 1rem 0;">Ningún operador ha realizado entregas aún.</p>';
                return;
            }

            s.forEach((operador, index) => {
                const fila = document.createElement('div');
                fila.className = 'staff-performance-row';
                fila.style = 'display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #E2E8F0;';
                fila.innerHTML = `
                    <span><b style="color:#64748B;">#${index + 1}</b> <strong style="color:#1E293B; margin-left:0.3rem;">${operador.operador}</strong></span>
                    <span class="badge-count" style="background-color: #EFF6FF; color: #1E40AF; padding: 0.25rem 0.8rem; border-radius: 20px; font-weight: 700; font-size: 0.85rem;">
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