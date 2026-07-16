// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: public/js/encargado.js (CONTROLADOR DE SUPERVISIÓN DEPURADO)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

let folioAtletaSeleccionado = null;

document.addEventListener('DOMContentLoaded', () => {

    const selectEvento = document.getElementById('select-evento-activo');
    const inputBuscar = document.getElementById('search-supervisor'); //SOLUCIÓN: Declarado globalmente en este bloque

    if (selectEvento) {
        // Inicialización inmediata al cargar la página
        if (selectEvento.value) {
            cargarEstadisticasSupervisor();
        }

        // Auto-refresco automático cada 15 segundos
        setInterval(() => {
            if (selectEvento.value) {
                cargarEstadisticasSupervisor();
            }
        }, 15000);

        // Escucha si el supervisor cambia de carrera en el select superior
        selectEvento.addEventListener('change', async (e) => {
            const nuevoEventoId = e.target.value;

            // Recargamos las métricas visuales del supervisor
            cargarEstadisticasSupervisor();

            //ENLACE CON REPORTE DE ERRORES:
            try {
                const response = await fetch('api/activar_evento.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ evento_id: nuevoEventoId })
                });

                // Procesamos la respuesta real del servidor PHP
                const data = await response.json();

                if (data.success) {
                    console.log(`%cSQL Server: Carrera ${nuevoEventoId} activada globalmente.`, "color: #10B981; font-weight: bold;");
                } else {
                    console.error("PHP rechazó la activación:", data.message);
                    alert("Atención: " + data.message);
                }
            } catch (err) {
                console.error("Error de red o ruta hacia activar_evento.php:", err);
            }

            //SOLUCIÓN: Ahora inputBuscar ya existe y no romperá el script
            if (inputBuscar && inputBuscar.value.trim().length >= 1) {
                buscarAtletaSupervisor();
            }
        });
    }
});

// Esta función actualiza el dashboard
window.cargarEstadisticasSupervisor = async function () {
    const selectEvento = document.getElementById('select-evento-activo');
    const inputBuscar = document.getElementById('search-supervisor');
    if (!selectEvento) return;

    const eventoId = selectEvento.value;
    if (!eventoId) return;

    try {
        const response = await fetch(`api/estadisticas.php?evento_id=${eventoId}`);
        const res = await response.json();

        if (!res.success) return;

        const r = res.resumen;
        const s = res.staff;


        if (document.getElementById('total-inscritos')) document.getElementById('total-inscritos').innerText = r.total.toLocaleString();
        if (document.getElementById('total-entregados')) document.getElementById('total-entregados').innerText = r.entregados.toLocaleString();
        if (document.getElementById('total-pendientes')) document.getElementById('total-pendientes').innerText = r.pendientes.toLocaleString();
        if (document.getElementById('total-cambios')) document.getElementById('total-cambios').innerText = r.cambios.toLocaleString();

        if (document.getElementById('txt-porcentaje-encargado')) {
            document.getElementById('txt-porcentaje-encargado').innerText = `Avance del ${r.porcentaje}%`;
        }


        const tbody = document.getElementById('tabla-productividad-body');
        const lblMesas = document.getElementById('lbl-mesas-activas');

        if (tbody) {
            tbody.innerHTML = '';
            if (lblMesas) lblMesas.innerText = `${s.length} Mesas Activas`;

            if (s.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Ningún staff ha registrado entregas aún en este evento.</td></tr>';
                return;
            }

            //ACTIVO-AUSENTE    
            s.forEach((staff, index) => {
                const tr = document.createElement('tr');

                //CAPTURA INTELIGENTE: Leemos el estatus del servidor (por defecto 'Ausente' si no viene)
                const estatusReal = staff.estatus_operativo || 'Ausente';

                //CONTROL DE ESTILOS: Si está activo usa tu clase 'online' (verde), si no, usa 'offline' (gris/rojo)
                const claseColor = (estatusReal === 'Activo') ? 'online' : 'offline';

                tr.innerHTML = `
                    <td><span class="table-badge-mesa">Mesa ${index + 1}</span></td>
                    <td><strong>${staff.operador}</strong></td>
                    <td class="text-center text-bold text-blue">${staff.total_entregas} kits</td>
                    <td><span class="badge-status ${claseColor}">● ${estatusReal}</span></td>
                `;
                tbody.appendChild(tr);
            });
        }


        if (inputBuscar && inputBuscar.value.trim().length >= 1) {
            buscarAtletaSupervisor();
        }

    } catch (error) {
        console.error("Error actualizando Dashboard de Supervisor:", error);
    }
}

// Esta función busca los atletas en la mesa del supervisor
window.buscarAtletaSupervisor = async function () {
    const inputBuscar = document.getElementById('search-supervisor');
    const selectEvento = document.getElementById('select-evento-activo');
    const contenedor = document.getElementById('resultados-supervisor');

    if (!inputBuscar || !contenedor) return;

    const valor = inputBuscar.value.trim();

    if (valor.length < 1) {
        contenedor.innerHTML = '<p class="text-center text-muted" style="grid-column: 1/-1;">Escriba los datos del atleta arriba para abrir las herramientas de edición.</p>';
        return;
    }

    const eventoId = selectEvento ? selectEvento.value : 0;

    try {

        const response = await fetch(`api/buscar.php?termino=${encodeURIComponent(valor)}&evento_id=${eventoId}`);
        const atletas = await response.json();

        contenedor.innerHTML = '';

        if (atletas.length === 0) {
            contenedor.innerHTML = '<p class="text-center text-danger" style="grid-column: 1/-1;">No se encontraron competidores.</p>';
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
        console.error("Error al buscar atletas segmentados:", error);
    }
}

// --- LOGICA DE MODAL DE INCIDENCIAS ---
window.abrirModalCategoria = async (folio, categoriaActual) => {
    folioAtletaSeleccionado = folio;
    document.getElementById('txt-folio-sup').innerText = folio;

    const selectEvento = document.getElementById('select-evento-activo');
    const eventoId = selectEvento ? selectEvento.value : 0;
    const selectCat = document.getElementById('select-categoria-sup');

    if (selectCat) {
        selectCat.innerHTML = '<option value="">Cargando categorías...</option>';

        try {
            // Jalamos el catálogo único de categorías del evento activo
            const response = await fetch(`api/obtener_categorias.php?evento_id=${eventoId}`);
            const data = await response.json();

            if (data.success) {
                selectCat.innerHTML = ''; // Limpiamos el mensaje de carga

                data.categorias.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat;
                    opt.textContent = cat;

                    // Si coincide con la categoría que ya tiene el corredor, la dejamos seleccionada
                    if (cat === categoriaActual) {
                        opt.selected = true;
                    }
                    selectCat.appendChild(opt);
                });
            }
        } catch (error) {
            console.error("Error cargando el catálogo de categorías dinámicas:", error);
            // Respaldo en caso de error de red: dejamos la opción actual del atleta
            selectCat.innerHTML = `<option value="${categoriaActual}" selected>${categoriaActual}</option>`;
        }
    }

    // Desplegamos el modal visualmente
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
                nueva_categoria: nuevaCat,
                evento_id: selectEvento ? selectEvento.value : 0
            })
        });

        const res = await response.json();

        if (res.success) {
            alert("Categoría reasignada con éxito. Registro guardado en la auditoría interna.");
            cerrarModalSupervisor();
            buscarAtletaSupervisor();
            if (selectEvento) cargarEstadisticasSupervisor();
        } else {
            alert("Error: " + res.message);
        }
    } catch (error) {
        console.error(error);
        alert("Error de comunicación con el servidor.");
    }
};
