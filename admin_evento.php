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
            <div class="metric-card card-blue">
                <h3>Inscritos Oficiales</h3>
                <p>1,500</p>
                <span class="card-footer-text">Atletas cargados desde .CSV</span>
            </div>
            <div class="metric-card card-green">
                <h3>Kits Entregados</h3>
                <p>950</p>
                <span class="card-footer-text">63.3% del total liberado</span>
            </div>
            <div class="metric-card card-yellow">
                <h3>Kits Pendientes</h3>
                <p>550</p>
                <span class="card-footer-text">Por recolectar en mesas</span>
            </div>
        </section>

        <section class="charts-layout-grid">

            <div class="admin-card">
                <h2>Estatus General de Entrega</h2>
                <p class="section-desc">Proporción visual de paquetes recolectados contra el inventario restante.</p>

                <div class="chart-placeholder-box">
                    <div class="mock-pie-chart">
                        <div class="pie-center-text">63.3%<br><span>Entregado</span></div>
                    </div>
                    <div class="chart-legends">
                        <div class="legend-item"><span class="legend-dot green"></span> Entregados (950)</div>
                        <div class="legend-item"><span class="legend-dot yellow"></span> Pendientes (550)</div>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h2>Distribución Logística por Mesas</h2>
                <p class="section-desc">Monitoreo del volumen total de kits validados por estación de atención.</p>

                <div class="chart-placeholder-box vertical-align">
                    <div class="mock-bar-row">
                        <span class="bar-label">Mesa 1 (Reymundo)</span>
                        <div class="bar-track">
                            <div class="bar-fill blue" style="width: 85%;"></div>
                        </div>
                        <span class="bar-value">340 kits</span>
                    </div>
                    <div class="mock-bar-row">
                        <span class="bar-label">Mesa 2 (Diego)</span>
                        <div class="bar-track">
                            <div class="bar-fill blue" style="width: 70%;"></div>
                        </div>
                        <span class="bar-value">280 kits</span>
                    </div>
                    <div class="mock-bar-row">
                        <span class="bar-label">Mesa 3 (Stephanie)</span>
                        <div class="bar-track">
                            <div class="bar-fill blue" style="width: 60%;"></div>
                        </div>
                        <span class="bar-value">240 kits</span>
                    </div>
                    <div class="mock-bar-row">
                        <span class="bar-label">Mesa 4 (Jorge)</span>
                        <div class="bar-track">
                            <div class="bar-fill blue" style="width: 25%;"></div>
                        </div>
                        <span class="bar-value">90 kits</span>
                    </div>
                </div>
            </div>

        </section>

    </main>

</body>

</html>