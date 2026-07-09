// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: public/js/buscador.js (BUSCADOR STAFF CON EXPULSIÓN EN TIEMPO REAL)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

document.addEventListener('DOMContentLoaded', () => {
    const inputBuscar = document.getElementById('search-input'); // Asegúrate que tu <input> de busqueda tenga este ID
    const contenedorResultados = document.getElementById('resultados-atletas'); // El contenedor <div> donde se pintarán las tarjetas

    if (inputBuscar && contenedorResultados) {
        inputBuscar.addEventListener('input', async (e) => {
            const valor = e.target.value.trim();

            if (valor.length < 1) {
                contenedorResultados.innerHTML = '<p class="text-center text-muted">Escriba al menos 1 caracter para buscar...</p>';
                return;
            }

            try {
                const response = await fetch(`api/buscar.php?termino=${encodeURIComponent(valor)}`);
                const atletas = await response.json();

                // Si el encargado conmutó el evento, frenamos el renderizado y recargamos la mesa
                if (atletas && atletas.error === 'evento_cambiado') {
                    alert("⚠️ El Encargado ha cambiado la competencia activa. La mesa de entrega se reiniciará.");
                    window.location.reload();
                    return;
                }

                contenedorResultados.innerHTML = ''; // Limpiamos lo anterior

                if (atletas.length === 0) {
                    contenedorResultados.innerHTML = '<p class="text-center text-danger">❌ No se encontró ningún competidor.</p>';
                    return;
                }

                // Generamos las tarjetas dinámicas con los datos de SQL Server
                atletas.forEach(atleta => {
                    const tarjeta = document.createElement('div');
                    tarjeta.className = 'card-atleta';

                    const esPendiente = (atleta.estatus_entrega === 'PENDIENTE');
                    const funcionClick = esPendiente ? `abrirFirma(${atleta.folio})` : `verDetalles(${atleta.folio})`;
                    const textoBoton = esPendiente ? 'Entregar Kit' : 'Ver Detalles';

                    // LOGICA DE CAMBIO DE COLOR: Si está entregado pero tiene el flag de cambio activo
                    let textoBadge = atleta.estatus_entrega;
                    let claseBadge = atleta.estatus_entrega.toLowerCase();

                    if (atleta.estatus_entrega === 'ENTREGADO' && atleta.hubo_cambio == 1) {
                        textoBadge = 'CAMBIO';
                        claseBadge = 'cambio'; // Nueva clase CSS
                    }

                    tarjeta.innerHTML = `
                        <div class="info">
                            <span class="folio">#${atleta.folio}</span>
                            <h3>${atleta.nombre}</h3>
                            <p>Categoría: <b>${atleta.categoria}</b></p>
                        </div>
                        <div class="status-badge ${claseBadge}">
                            ${textoBadge}
                        </div>
                        <button class="btn-action" onclick="${funcionClick}">
                            ${textoBoton}
                        </button>
                    `;
                    contenedorResultados.appendChild(tarjeta);
                });

            } catch (error) {
                console.error('Error buscando competidores:', error);
            }
        });
    }
});
