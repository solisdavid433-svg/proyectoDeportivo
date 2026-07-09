// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: public/js/dashboard.js (MANEJADOR ANALÍTICO CON EDICIÓN DE ATLETAS)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

document.addEventListener('DOMContentLoaded', () => {
    // Ejecutamos la carga de estadísticas de inmediato al entrar
    cargarEstadisticas();

    // Opcional: Refrescar automáticamente cada 30 segundos para ver cambios en vivo
    setInterval(cargarEstadisticas, 30000);

    // ENLACE DEL LOCALIZADOR DE COMPETIDORES SEGMENTADO
    const inputBuscar = document.getElementById('search-admin-atleta-evento');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', async (e) => {
            ejecutarBusquedaAdmin(e.target.value.trim());
        });
    }
});

// Separamos la consulta de búsqueda en una función dedicada para poder re-llamarla tras editar
async function ejecutarBusquedaAdmin(valor) {
    const tbody = document.getElementById('tabla-admin-competidores-body');
    if (!tbody) return;

    if (valor.length < 1) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted" style="padding: 2rem;">
                    Escriba el nombre o folio en el buscador superior para consultar el estatus.
                </td>
            </tr>`;
        return;
    }

    const eventoId = document.getElementById('hdn-evento-id')?.value || 1;

    try {
        const response = await fetch(`api/buscar.php?termino=${encodeURIComponent(valor)}&evento_id=${eventoId}`);
        const atletas = await response.json();

        tbody.innerHTML = '';

        if (!atletas || atletas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger" style="padding: 1.5rem;">❌ No se encontraron competidores en esta carrera bajo ese término.</td></tr>';
            return;
        }

        atletas.slice(0, 10).forEach(atleta => {
            const estatus = atleta.estatus_entrega.toUpperCase();

            const badgeClass = (estatus === 'ENTREGADO')
                ? "background-color: #D1FAE5; color: #065F46; font-weight: bold; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;"
                : "background-color: #F3F4F6; color: #374151; font-weight: bold; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;";

            // Escapamos comillas simples en el nombre y categoría para evitar rupturas en el onclick string
            const nombreEscapado = atleta.nombre.replace(/'/g, "\\'");
            const catEscapada = atleta.categoria.replace(/'/g, "\\'");

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><span style="background-color: #EFF6FF; color: #1E40AF; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: bold; font-size: 0.85rem;">#${atleta.folio}</span></td>
                <td><b style="color: #0F172A;">${atleta.nombre}</b></td>
                <td><span style="color: #475569; font-size: 0.9rem;">${atleta.categoria}</span></td>
                <td><span style="${badgeClass}">${estatus}</span></td>
                <td>
                    <button class="btn-action edit" onclick="abrirModalEditarCompetidor(${atleta.folio}, '${nombreEscapado}', '${catEscapada}')" style="padding: 0.35rem 0.65rem; font-size: 0.8rem; cursor:pointer;">📝 Editar</button>
                </td>
            `;
            tbody.appendChild(tr);
        });

    } catch (error) {
        console.error("Error en el localizador analítico de competidores:", error);
    }
}

// ==========================================================================
// CONTROLADORES GLOBALES DEL MODAL DE EDICIÓN DE COMPETIDORES
// ==========================================================================
const modalAtletaAdmin = document.getElementById('modal-admin-competidor');

window.abrirModalEditarCompetidor = (folio, nombre, categoria) => {
    document.getElementById('form-admin-competidor').reset();

    // Auto-poblamos los campos con los datos de la fila
    document.getElementById('lbl_comp_folio').value = `#${folio}`;
    document.getElementById('comp_folio').value = folio;
    document.getElementById('comp_nombre').value = nombre;
    document.getElementById('comp_categoria').value = categoria;

    if (modalAtletaAdmin) modalAtletaAdmin.style.display = 'flex';
};

window.cerrarModalAdminCompetidor = () => {
    if (modalAtletaAdmin) modalAtletaAdmin.style.display = 'none';
};

window.guardarCambioCompetidor = async (event) => {
    event.preventDefault();
    const btn = document.getElementById('btn-submit-atleta-admin');
    btn.disabled = true; btn.innerText = "⏳ Actualizando padrón...";

    try {
        const response = await fetch('api/modificar_competidor.php', {
            method: 'POST',
            body: new FormData(document.getElementById('form-admin-competidor'))
        });
        const res = await response.json();

        if (res.success) {
            cerrarModalAdminCompetidor();

            // 🚀 REFRESCADO INTELIGENTE: Forzamos al buscador a lanzar la consulta con el texto actual
            const txtBuscar = document.getElementById('search-admin-atleta-evento')?.value || '';
            ejecutarBusquedaAdmin(txtBuscar);

            // Recargamos métricas por si cambió el inventario textil o barra de avance
            cargarEstadisticas();
        } else {
            alert("❌ Error de consistencia: " + res.message);
        }
    } catch (err) {
        console.error(err);
        alert("Error de red al intentar conectar con modificar_competidor.php");
    } finally {
        btn.disabled = false;
        btn.innerText = "💾 Actualizar Atleta";
    }
};

// ==========================================================================
// RECOLECTOR DE ANALÍTICAS ORIGINAL (CONSERVADO INTACTO 👍)
// ==========================================================================
async function cargarEstadisticas() {
    try {
        const eventoId = document.getElementById('hdn-evento-id')?.value || 1;
        const response = await fetch(`api/estadisticas.php?evento_id=${eventoId}`);
        const res = await response.json();

        if (!res.success) return;

        const r = res.resumen;
        const t = res.tallas;
        const s = res.staff;


        if (document.getElementById('lbl-total-atletas')) document.getElementById('lbl-total-atletas').innerText = r.total;
        if (document.getElementById('lbl-entregados')) document.getElementById('lbl-entregados').innerText = r.entregados;
        if (document.getElementById('lbl-pendientes')) document.getElementById('lbl-pendientes').innerText = r.pendientes;
        if (document.getElementById('lbl-cambios')) document.getElementById('lbl-cambios').innerText = r.cambios;


        const barraProgreso = document.getElementById('barra-progreso-global');
        const textoProgreso = document.getElementById('txt-porcentaje-global');
        if (barraProgreso && textoProgreso) {
            barraProgreso.style.width = `${r.porcentaje}%`;
            textoProgreso.innerText = `${r.porcentaje}% Completado`;
        }


        if (document.getElementById('txt-talla-xs')) document.getElementById('txt-talla-xs').innerText = `${t.XS.entregados} / ${t.XS.total}`;
        if (document.getElementById('txt-talla-ch')) document.getElementById('txt-talla-ch').innerText = `${t.CH.entregados} / ${t.CH.total}`;
        if (document.getElementById('txt-talla-m')) document.getElementById('txt-talla-m').innerText = `${t.M.entregados} / ${t.M.total}`;
        if (document.getElementById('txt-talla-g')) document.getElementById('txt-talla-g').innerText = `${t.G.entregados} / ${t.G.total}`;
        if (document.getElementById('txt-talla-xg')) document.getElementById('txt-talla-xg').innerText = `${t.XG.entregados} / ${t.XG.total}`;
        if (document.getElementById('txt-talla-2xl')) document.getElementById('txt-talla-2xl').innerText = `${t['2XL'].entregados} / ${t['2XL'].total}`;


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