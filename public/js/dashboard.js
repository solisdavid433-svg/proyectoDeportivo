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

// TRANSFORMA LA FUNCIÓN A ASÍNCRONA (async)
window.abrirModalEditarCompetidor = async (folio, nombre, categoria) => {
    document.getElementById('form-admin-competidor').reset();

    // Auto-poblamos los campos de texto con los datos de la fila
    document.getElementById('lbl_comp_folio').value = `#${folio}`;
    document.getElementById('comp_folio').value = folio;
    document.getElementById('comp_nombre').value = nombre;

    //SOLUCIÓN: Jalamos el ID real desde el input oculto de admin_evento.php
    const hdnEvento = document.getElementById('hdn-evento-id');
    const eventoId = hdnEvento ? parseInt(hdnEvento.value) : 0;

    const selectCat = document.getElementById('comp_categoria');

    if (selectCat) {
        selectCat.innerHTML = '<option value="">Cargando categorías...</option>';

        try {
            // Consultamos la API enviando el ID correcto del evento
            const response = await fetch(`api/obtener_categorias.php?evento_id=${eventoId}`);
            const data = await response.json();

            if (data.success && data.categorias.length > 0) {
                selectCat.innerHTML = ''; // Limpiamos el mensaje de carga

                data.categorias.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat;
                    opt.textContent = cat;

                    // Si coincide con la categoría actual del atleta, la pre-seleccionamos
                    if (cat === categoria) {
                        opt.selected = true;
                    }
                    selectCat.appendChild(opt);
                });
            } else {
                // Modo respaldo: Si no hay categorías, dejamos la que ya tiene el atleta
                selectCat.innerHTML = `<option value="${categoria}" selected>${categoria}</option>`;
            }
        } catch (err) {
            console.error("Error crítico en la petición FETCH del Admin:", err);
            selectCat.innerHTML = `<option value="${categoria}" selected>${categoria}</option>`;
        }
    }

    if (modalAtletaAdmin) modalAtletaAdmin.style.display = 'flex';
};

window.cerrarModalAdminCompetidor = () => {
    if (modalAtletaAdmin) modalAtletaAdmin.style.display = 'none';
};



// ==========================================================================
// RECOLECTOR DE ANALÍTICAS ORIGINAL (CONSERVADO INTACTO)
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

// ==========================================================================
// GESTIÓN DE ALTAS MANUALES DE COMPETIDORES
// ==========================================================================
const modalNuevoCompetidor = document.getElementById('modal-nuevo-competidor');

window.abrirModalNuevoCompetidor = async () => {
    document.getElementById('form-nuevo-competidor').reset();

    const hdnEvento = document.getElementById('hdn-evento-id');
    const eventoId = hdnEvento ? parseInt(hdnEvento.value) : 0;
    const selectCat = document.getElementById('nuevo_comp_categoria');

    if (selectCat) {
        selectCat.innerHTML = '<option value="">Cargando categorías...</option>';
        try {
            // Carga dinámicamente las categorías reales que tiene este evento
            const response = await fetch(`api/obtener_categorias.php?evento_id=${eventoId}`);
            const data = await response.json();

            if (data.success && data.categorias.length > 0) {
                selectCat.innerHTML = '<option value="">-- Seleccione Categoría --</option>';
                data.categorias.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat;
                    opt.textContent = cat;
                    selectCat.appendChild(opt);
                });
            } else {
                selectCat.innerHTML = '<option value="">Sin categorías previas</option>';
            }
        } catch (err) {
            console.error("Error al obtener categorías para nuevo competidor:", err);
        }
    }

    if (modalNuevoCompetidor) modalNuevoCompetidor.style.display = 'flex';
};

window.cerrarModalNuevoCompetidor = () => {
    if (modalNuevoCompetidor) modalNuevoCompetidor.style.display = 'none';
};

window.guardarNuevoCompetidor = async (event) => {
    event.preventDefault();
    const btn = document.getElementById('btn-submit-nuevo-atleta');
    const hdnEvento = document.getElementById('hdn-evento-id');
    const eventoId = hdnEvento ? parseInt(hdnEvento.value) : 0;

    btn.disabled = true;
    btn.innerText = "Registrando...";

    const formData = new FormData(document.getElementById('form-nuevo-competidor'));
    formData.append('evento_id', eventoId); // Inyectamos el ID de la carrera activa

    try {
        const response = await fetch('api/agregar_competidor.php', {
            method: 'POST',
            body: formData
        });
        const res = await response.json();

        if (res.success) {
            alert("✅ " + res.message);
            cerrarModalNuevoCompetidor();

            // Refrescamos métricas para que "Total Inscritos" suba de inmediato
            if (typeof cargarEstadisticas === 'function') cargarEstadisticas();

            // Ejecutamos una búsqueda automática con el folio creado para verificarlo
            const folioCreado = document.getElementById('nuevo_comp_folio').value;
            const inputBuscar = document.getElementById('search-admin-atleta-evento');
            if (inputBuscar) {
                inputBuscar.value = folioCreado;
                if (typeof ejecutarBusquedaAdmin === 'function') ejecutarBusquedaAdmin(folioCreado);
            }
        } else {
            alert("⚠️ " + res.message);
        }
    } catch (err) {
        console.error(err);
        alert("Error de conexión al intentar agregar al competidor.");
    } finally {
        btn.disabled = false;
        btn.innerText = "Dar de Alta";
    }
};