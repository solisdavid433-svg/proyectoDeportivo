// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: public/js/encargado.js (CONTROLADOR GLOBAL DE DASHBOARD E INCIDENCIAS)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

let folioAtletaSeleccionado = null;

document.addEventListener('DOMContentLoaded', () => {
    const selectEvento = document.getElementById('select-evento-activo');
    const inputBuscar = document.getElementById('search-supervisor');

    // 1. ARRANCAR EL DASHBOARD EN VIVO
    if (selectEvento) {
        // Carga inmediata al entrar
        cargarMetricasDashboard(selectEvento.value);

        // Escucha si el supervisor cambia de carrera en el select superior
        selectEvento.addEventListener('change', (e) => {
            cargarMetricasDashboard(e.target.value);
        });

        // Auto-refresco en vivo cada 15 segundos
        setInterval(() => {
            cargarMetricasDashboard(selectEvento.value);
        }, 15000);
    }

    // 2. CONTROL DEL BUSCADOR DE CORRECCIONES
    if (inputBuscar) {
        inputBuscar.addEventListener('input', async (e) => {
            const contenedor = document.getElementById('resultados-supervisor');
            const valor = e.target.value.trim();

            if (valor.length < 2) {
                contenedor.innerHTML = '<p class="text-center text-muted" style="grid-column: 1/-1;">Escriba los datos del atleta arriba para abrir las herramientas de edición.</p>';
                return;
            }

            try {
                const response = await fetch(`api/buscar.php?termino=${encodeURIComponent(valor)}`);
                const atletas = await response.json();

                contenedor.innerHTML = '';

                if (atletas.length === 0) {
                    contenedor.innerHTML = '<p class="text-center text-danger" style="grid-column: 1/-1;">❌ No se encontraron competidores inscritos bajo ese término.</p>';
                    return;
                }

                atletas.forEach(atleta => {
                    const tarjeta = document.createElement('div');
                    tarjeta.className = 'card-atleta';
                    tarjeta.style = 'background: white; border: 1px solid #E2E8F0; padding: 1rem; border-radius: 0.5rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05);';

                    tarjeta.innerHTML = `
                        <div class="info">
                            <span class="folio" style="background: #EFF6FF; color: #1E40AF; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight:700; font-size:0.85rem;">#${atleta.folio}</span>
                            <h3 style="margin: 0.4rem 0 0.2rem 0; font-size: 1.1rem; color:#0F172A;">${atleta.nombre}</h3>
                            <p style="margin:0; font-size:0.9rem; color:#64748B;">Categoría: <b style="color:#1E3A8A;">${atleta.categoria}</b> | Estatus: <b>${atleta.estatus_entrega}</b></p>
                        </div>
                        <button class="btn" onclick="abrirModalCategoria(${atleta.folio}, '${atleta.categoria}')" style="background-color: #2563EB; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer; font-size:0.9rem;">
                            ⚙️ Reasignar Categoría
                        </button>
                    `;
                    contenedor.appendChild(tarjeta);
                });

            } catch (error) {
                console.error("Error al buscar atletas:", error);
            }
        });
    }
});

// --- FUNCIÓN ASÍNCRONA: CARGA DE MÉTRICAS Y TABLA DE STAFF ---
async function cargarMetricasDashboard(eventoId) {
    try {
        const response = await fetch(`api/estadisticas.php?evento_id=${eventoId}`);
        const res = await response.json();

        if (!res.success) return;

        const r = res.resumen;
        const s = res.staff;

        // Inyectamos las variables de SQL Server en las tarjetas métricas
        if (document.getElementById('total-inscritos')) document.getElementById('total-inscritos').innerText = r.total.toLocaleString();
        if (document.getElementById('total-entregados')) document.getElementById('total-entregados').innerText = r.entregados.toLocaleString();
        if (document.getElementById('total-pendientes')) document.getElementById('total-pendientes').innerText = r.pendientes.toLocaleString();
        if (document.getElementById('txt-porcentaje-encargado')) document.getElementById('txt-porcentaje-encargado').innerText = `Avance del ${r.porcentaje}%`;

        // Actualizamos la tabla de productividad del staff dinámicamente
        const tbody = document.getElementById('tabla-productividad-body');
        const lblMesas = document.getElementById('lbl-mesas-activas');

        if (tbody) {
            tbody.innerHTML = '';
            if (lblMesas) lblMesas.innerText = `${s.length} Mesas Activas`;

            if (s.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Ningún staff ha registrado entregas aún en este evento.</td></tr>';
                return;
            }

            s.forEach((staff, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><span class="table-badge-mesa">Mesa ${index + 1}</span></td>
                    <td><strong>${staff.operador}</strong></td>
                    <td class="text-center text-bold text-blue">${staff.total_entregas} kits</td>
                    <td><span class="badge-status online">● Activo</span></td>
                `;
                tbody.appendChild(tr);
            });
        }

    } catch (error) {
        console.error("Error actualizando Dashboard de Supervisor:", error);
    }
}

// --- LOGICA DE APERTURA Y CIERRE DEL MODAL DE SUPERVISIÓN ---
window.abrirModalCategoria = (folio, categoriaActual) => {
    folioAtletaSeleccionado = folio;
    document.getElementById('txt-folio-sup').innerText = folio;
    document.getElementById('select-categoria-sup').value = categoriaActual;
    document.getElementById('modal-supervisor').style.display = 'flex';
};

window.cerrarModalSupervisor = () => {
    document.getElementById('modal-supervisor').style.display = 'none';
    folioAtletaSeleccionado = null;
};

window.guardarCambioCategoria = async () => {
    const nuevaCat = document.getElementById('select-categoria-sup').value;
    const selectEvento = document.getElementById('select-evento-activo');

    try {
        const response = await fetch('api/cambiar_categoria.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                folio: folioAtletaSeleccionado,
                nueva_categoria: nuevaCat
            })
        });

        const res = await response.json();

        if (res.success) {
            alert("✔️ Categoría reasignada con éxito. Registro guardado en la auditoría interna.");
            cerrarModalSupervisor();

            // Forzamos al buscador y al marcador del dashboard a actualizarse
            document.getElementById('search-supervisor').dispatchEvent(new Event('input'));
            if (selectEvento) cargarMetricasDashboard(selectEvento.value);
        } else {
            alert("❌ Error: " + res.message);
        }
    } catch (error) {
        console.error(error);
        alert("❌ Error de comunicación con el servidor.");
    }
};