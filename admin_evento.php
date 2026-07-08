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

<?php
// 🎯 CAPTURA CORRECTA: Leemos '?id=X' de la URL de admin_evento.php
$evento_id_visualizando = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Nombre de respaldo por si ocurre algún fallo
$nombre_evento_cabecera = "Módulo Analítico";

// Consultamos el nombre real del circuito en SQL Server
$sql_header_name = "SELECT nombre_evento FROM tbl_eventos WHERE id = ?";
$stmt_header_name = sqlsrv_query($conn, $sql_header_name, array($evento_id_visualizando));

if ($stmt_header_name && $row_hn = sqlsrv_fetch_array($stmt_header_name, SQLSRV_FETCH_ASSOC)) {
    $nombre_evento_cabecera = $row_hn['nombre_evento'];
}
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
                <h1 class="event-title"><?php echo htmlspecialchars($nombre_evento_cabecera); ?></h1>
                <p class="event-subtitle">Control analítico y estatus en tiempo real de la entrega de paquetes.</p>
            </div>

            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.75rem;">

                <a href="api/exportar_competidores.php?id=<?php echo $evento_id_visualizando; ?>"
                    target="_blank"
                    class="btn"
                    style="background-color: #10B981; color: white; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.1rem; font-weight: 700; border-radius: 6px; text-decoration: none; font-size: 0.85rem; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2); transition: background-color 0.2s; border: none; cursor: pointer;">
                    📊 Exportar Lista Excel
                </a>

                <!-- 🎯 NUEVO BOTÓN 2: LA PLANTILLA COMPATIBLE CON EL SEÑOR CARLOS -->
                <a href="api/exportar_cronometraje.php?id=<?php echo $evento_id_visualizando; ?>" target="_blank" class="btn" style="background-color: #475569; color: white; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.1rem; font-weight: 700; border-radius: 6px; text-decoration: none; font-size: 0.85rem; box-shadow: 0 4px 6px -1px rgba(71, 85, 105, 0.2);">
                    ⏱️ Descargar Plantilla Cronometraje
                </a>

                <div class="refresh-indicator" style="margin: 0;">
                    <span class="status-dot pulse"></span> Monitoreo en vivo activo
                </div>

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

                    <div style="position: relative; min-width: 220px;">
                        <input type="text" id="search-admin-atleta-evento" placeholder="🔍 Buscar folio o nombre..."
                            style="padding: 0.55rem 0.8rem; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 0.85rem; width: 100%; box-sizing: border-box; outline: none;">
                    </div>
                </div>


                <div class="table-responsive" style="margin-top: 1.2rem; max-height: 260px; overflow-y: auto; flex-grow: 1;">
                    <table class="admin-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Nombre del Competidor</th>
                                <th>Categoría</th>
                                <th>Estatus</th>
                                <th>Acción</th>
                            </tr>
                        </thead>

                        <tbody id="tabla-admin-competidores-body">
                            <tr>
                                <td colspan="5" class="text-center text-muted" style="padding: 2rem;">
                                    Escriba el nombre o folio en el buscador superior para consultar el estatus.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!--modal de edición de competidor -->

            <div id="modal-admin-competidor" class="modal" style="display: none;">
                <div class="modal-content" style="max-width: 460px; box-sizing: border-box;">
                    <span class="close-modal" onclick="cerrarModalAdminCompetidor()">&times;</span>
                    <h2 style="color: #0F172A; border-bottom: 2px solid #E2E8F0; padding-bottom: 0.5rem; margin-top: 0;">🏃‍♂️ Corregir Datos de Atleta</h2>
                    <p class="section-desc" style="margin-bottom: 1.5rem;">Modifique errores de inscripción o reasigne categorías para el kit.</p>

                    <form id="form-admin-competidor" onsubmit="guardarCambioCompetidor(event)" style="text-align: left; display: grid; gap: 1.25rem;">
                        <div class="form-group">
                            <label style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Número de Folio (Chip):</label>
                            <input type="text" id="lbl_comp_folio" readonly style="width: 100%; padding: 0.65rem; border: 1px solid #E2E8F0; background-color: #F8FAFC; border-radius: 6px; box-sizing: border-box; font-weight: bold; color: #1E40AF;">
                            <input type="hidden" id="comp_folio" name="comp_folio">
                        </div>

                        <div class="form-group">
                            <label style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Nombre Completo del Atleta:</label>
                            <input type="text" id="comp_nombre" name="comp_nombre" required style="width: 100%; padding: 0.65rem; border: 1px solid #CBD5E1; border-radius: 6px; box-sizing: border-box; font-weight: 600;">
                        </div>

                        <div class="form-group">
                            <label style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Categoría de Circuito:</label>
                            <input type="text" id="comp_categoria" name="comp_categoria" required style="width: 100%; padding: 0.65rem; border: 1px solid #CBD5E1; border-radius: 6px; box-sizing: border-box; text-transform: uppercase;">
                        </div>

                        <div class="modal-actions" style="display: flex; gap: 1rem; justify-content: center; width: 100%; margin-top: 0.5rem;">
                            <button type="button" onclick="cerrarModalAdminCompetidor()" style="background-color: #F1F5F9; color: #334155; border: 1px solid #CBD5E1; padding: 0.65rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer;">Cancelar</button>
                            <button type="submit" id="btn-submit-atleta-admin" style="background-color: #1E40AF; color: white; border: none; padding: 0.65rem 1.5rem; border-radius: 6px; font-weight: 700; cursor: pointer; flex-grow: 1;">💾 Actualizar Atleta</button>
                        </div>
                    </form>
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