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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas del Evento - Proyecto Deportivo</title>
    <link rel="stylesheet" href="public/css/styles.css">
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
                <h2>Inventario por playeras entregadas</h2>
                <div class="tallas-stats-box" style="margin-top: 1.25rem; font-size: 0.95rem; color: #334155;">
                    <div style="display: flex; justify-content: space-between; padding: 0.65rem 0.5rem; border-bottom: 1px solid #E2E8F0;">
                        <span>👕 Talla Chica (CH):</span>
                        <b id="txt-talla-ch" style="color: #0F172A; font-weight: 700;">0 pzas</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.65rem 0.5rem; border-bottom: 1px solid #E2E8F0;">
                        <span>👕 Talla Mediana (M):</span>
                        <b id="txt-talla-m" style="color: #0F172A; font-weight: 700;">0 pzas</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.65rem 0.5rem; border-bottom: 1px solid #E2E8F0;">
                        <span>👕 Talla Grande (G):</span>
                        <b id="txt-talla-g" style="color: #0F172A; font-weight: 700;">0 pzas</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.65rem 0.5rem;">
                        <span>👕 Talla Extra Grande (XG):</span>
                        <b id="txt-talla-xg" style="color: #0F172A; font-weight: 700;">0 pzas</b>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h2>Distribución Logística por Mesas</h2>
                <p class="section-desc">Monitoreo del volumen total de kits validados por estación de atención.</p>
                <div class="staff-ranking-box">
                    <h3>Rendimiento de Mesas en Vivo</h3>
                    <div id="lista-rendimiento-staff">
                        <!-- Aquí JavaScript pintará las filas dinámicamente -->
                    </div>
                </div>
            </div>

        </section>

    </main>
    <script src="public/js/dashboard.js?v=1.2"></script>
</body>

</html>