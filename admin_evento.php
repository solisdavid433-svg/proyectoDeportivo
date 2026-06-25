<?php
session_start();

// Bloque anti-caché: Obliga al navegador a consultar al servidor siempre
header("Cache-Control: no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Si no existe la sesión de rol O el rol no es Administrador, lo expulsamos
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    header('Location: index.php');
    exit();
}
// Capturamos el ID del evento que se está consultando
$evento_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

require_once 'config/db.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas del Evento - Proyecto Deportivo</title>
    <link rel="stylesheet" href="public/css/styles.css">
    <link rel="icon" href="public/img/logo.png" type="image/png">
</head>

<body class="bg-light">

    <!-- CRÍTICO: Este input oculto le permitirá a JavaScript saber qué evento estamos auditando -->
    <input type="hidden" id="hdn-evento-id" value="<?php echo $evento_id; ?>">

    <header class="navbar">
        <div class="navbar-brand">
            <img src="public/img/logo.png" alt="Proyecto Deportivo" class="nav-logo">
            <span>Módulo Administrador</span>
        </div>
        <div class="navbar-user">
            <span class="user-badge">Admin</span>
            <button id="btn-logout" class="btn-logout" onclick="window.location.href='api/logout.php'">Cerrar Sesión</button>
        </div>
    </header>

    <main class="dashboard-container">

        <section class="event-dashboard-header">
            <div>
                <button onclick="window.location.href='admin.php'" class="btn-back">← Volver a Competencias</button>
                <h1 class="event-title">Carrera Atlética Morelia 10K 2026</h1>
                <p class="event-subtitle">Control analítico y estatus en tiempo real de la entrega de paquetes.</p>
            </div>
            <div class="refresh-indicator">
                <span class="status-dot pulse"></span> Monitoreo en vivo activo
            </div>
        </section>

        <section class="metrics-grid">
            <div class="card">
                <h3>Total Inscritos</h3>
                <p id="lbl-total-atletas">-</p>
            </div>
            <div class="card">
                <h3>Kits Entregados</h3>
                <p id="lbl-entregados">-</p>
            </div>
            <div class="card">
                <h3>Pendientes por Recoger</h3>
                <p id="lbl-pendientes">-</p>
            </div>
            <div class="card">
                <h3>Incidencias / Cambios</h3>
                <p id="lbl-cambios">-</p>
            </div>
        </section>

        <section class="charts-layout-grid">

            <div class="admin-card">
                <h2>Estatus General de Entrega</h2>
                <p class="section-desc">Proporción visual de paquetes recolectados contra el inventario restante.</p>

                <div class="chart-placeholder-box">
                    <div class="progress-container">
                        <span id="txt-porcentaje-global">0% Completado</span>
                        <div class="progress-bar-background">
                            <!-- El JS alterará el width en porcentaje de este div -->
                            <div id="barra-progreso-global" class="progress-bar-fill" style="width: 0%;"></div>
                        </div>
                        <div class="progress-scale-labels">
                            <span>0% Inicio</span>
                            <span>50%</span>
                            <span>100% Meta</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h2>Inventario de Playeras Entregadas</h2>
                <p class="section-desc">Volumen de prendas repartidas por tamaño para el control de stock en almacén.</p>

                <div class="tallas-stats-box" style="margin-top: 1.25rem; font-size: 0.95rem; color: #334155;">
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem; border-bottom: 1px solid #E2E8F0;">
                        <span>👕 Talla Extra Chica (XS):</span>
                        <b id="txt-talla-xs" style="color: #0F172A; font-weight: 700;">0 / 0</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem; border-bottom: 1px solid #E2E8F0;">
                        <span>👕 Talla Chica (CH):</span>
                        <b id="txt-talla-ch" style="color: #0F172A; font-weight: 700;">0 / 0</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem; border-bottom: 1px solid #E2E8F0;">
                        <span>👕 Talla Mediana (M):</span>
                        <b id="txt-talla-m" style="color: #0F172A; font-weight: 700;">0 / 0</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem; border-bottom: 1px solid #E2E8F0;">
                        <span>👕 Talla Grande (G):</span>
                        <b id="txt-talla-g" style="color: #0F172A; font-weight: 700;">0 / 0</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem; border-bottom: 1px solid #E2E8F0;">
                        <span>👕 Talla Extra Grande (XG):</span>
                        <b id="txt-talla-xg" style="color: #0F172A; font-weight: 700;">0 / 0</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem;">
                        <span>👕 Talla Doble Extra Grande (2XL):</span>
                        <b id="txt-talla-2xl" style="color: #0F172A; font-weight: 700;">0 / 0</b>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================================================
     FILA DE CONTROL INFERIOR: RENDIMIENTO STAFF Y CONTROL DE COMPETIDORES
     ========================================================================== -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 2rem; align-items: start; width: 100%; box-sizing: border-box;">
            <!--TARJETA IZQUIERDA: MONITOREO Y BÚSQUEDA DE COMPETIDORES EN ESTE EVENTO -->
            <div class="admin-card" style="margin: 0; min-height: 380px; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: start; gap: 1rem; flex-wrap: wrap;">
                    <div>
                        <h3>Localizador de Competidores</h3>
                        <p class="section-desc">Consulte el estatus de entrega y datos de atletas en esta competencia.</p>
                    </div>
                    <!-- Campo de Texto integrado estéticamente -->
                    <div style="position: relative; min-width: 220px;">
                        <input type="text" id="search-admin-atleta-evento" placeholder="🔍 Buscar folio o nombre..."
                            style="padding: 0.55rem 0.8rem; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 0.85rem; width: 100%; box-sizing: border-box; outline: none; transition: border 0.2s;">
                    </div>
                </div>

                <!-- Tabla de visualización rápida con Scroll de Seguridad -->
                <div class="table-responsive" style="margin-top: 1.2rem; max-height: 260px; overflow-y: auto; flex-grow: 1;">
                    <table class="admin-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Nombre del Competidor</th>
                                <th>Categoría</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <!-- El contenedor dinámico que poblará el dashboard.js -->
                        <tbody id="tabla-admin-competidores-body">
                            <tr>
                                <td colspan="4" class="text-center text-muted" style="padding: 2rem;">
                                    Escriba el nombre o folio en el buscador superior para consultar el estatus.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TARJETA DERECHA: RENDIMIENTO DE STAFF (TU DISEÑO ACTUAL) -->
            <div class="admin-card" style="margin: 0; min-height: 380px;">
                <h3>Distribución Logística por Mesas</h3>
                <p class="section-desc">Monitoreo del volumen total de kits validados por estación de atención.</p>

                <h4 style="margin-top: 1.5rem; color: #0F172A;">Rendimiento de Mesas en Vivo</h4>
                <!-- El contenedor dinámico de tu dashboard.js -->
                <div id="lista-rendimiento-staff" style="margin-top: 1rem;">
                    <!-- Carga dinámica asíncrona -->
                </div>
            </div>
        </div>
    </main>
    <script src="public/js/dashboard.js?v=1.2"></script>
</body>

</html>