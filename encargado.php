<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Supervisión (Encargado) - Proyecto Deportivo</title>
    <link rel="stylesheet" href="public/css/styles.css">
</head>

<body class="bg-light">

    <header class="navbar">
        <div class="navbar-brand">
            <img src="public/img/logo.png" alt="Proyecto Deportivo" class="nav-logo">
            <span>Módulo Encargado (Supervisor)</span>
        </div>
        <div class="navbar-user">
            <span class="user-badge supervisor">Encargado</span>
            <button id="btn-logout" class="btn-logout">Cerrar Sesión</button>
        </div>
    </header>

    <main class="dashboard-container">

        <section class="supervisor-controls">
            <div class="control-box-select">
                <label for="select-evento-activo" class="label-bold">Monitorear Competencia:</label>
                <select id="select-evento-activo" class="form-select">
                    <option value="1">Carrera Atlética Morelia 10K 2026 (Activo)</option>
                    <option value="2">Triatlón Regional Cuitzeo 2026</option>
                </select>
            </div>
            <div class="refresh-indicator">
                <span class="status-dot pulse"></span> Actualizando automáticamente en vivo
            </div>
        </section>

        <section class="metrics-grid">
            <div class="metric-card card-blue">
                <h3>Total Inscritos</h3>
                <p id="total-inscritos">1,250</p>
                <span class="card-footer-text">Competidores totales</span>
            </div>
            <div class="metric-card card-green">
                <h3>Kits Entregados</h3>
                <p id="total-entregados">450</p>
                <span class="card-footer-text">Avance del 36%</span>
            </div>
            <div class="metric-card card-yellow">
                <h3>Kits Pendientes</h3>
                <p id="total-pendientes">800</p>
                <span class="card-footer-text">En inventario de mesas</span>
            </div>
        </section>

        <section class="admin-card full-width">
            <div class="card-header-box">
                <div>
                    <h2>Productividad del Staff en Campo</h2>
                    <p class="section-desc">Monitoree cuántos paquetes ha validado individualmente cada operador en las mesas.</p>
                </div>
                <span class="table-summary-badge">4 Mesas Activas</span>
            </div>

            <div class="table-responsive">
                <table class="admin-table data-table-centered">
                    <thead>
                        <tr>
                            <th>Mesa</th>
                            <th>Nombre del Operador (Staff)</th>
                            <th class="text-center">Kits Validados con Firma</th>
                            <th>Última Actividad Registrada</th>
                            <th>Estatus Conexión</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="table-badge-mesa">Mesa 1</span></td>
                            <td><strong>Reymundo Pérez</strong></td>
                            <td class="text-center text-bold text-blue">150 kits</td>
                            <td>Hace 2 min (Folio #1045)</td>
                            <td><span class="badge-status online">● Activo</span></td>
                        </tr>
                        <tr>
                            <td><span class="table-badge-mesa">Mesa 2</span></td>
                            <td><strong>Diego Granados</strong></td>
                            <td class="text-center text-bold text-blue">120 kits</td>
                            <td>Hace 5 min (Folio #1021)</td>
                            <td><span class="badge-status online">● Activo</span></td>
                        </tr>
                        <tr>
                            <td><span class="table-badge-mesa">Mesa 3</span></td>
                            <td><strong>Stephanie Villanueva</strong></td>
                            <td class="text-center text-bold text-blue">115 kits</td>
                            <td>Hace 1 min (Folio #1050)</td>
                            <td><span class="badge-status online">● Activo</span></td>
                        </tr>
                        <tr>
                            <td><span class="table-badge-mesa">Mesa 4</span></td>
                            <td><strong>Jorge Salgado</strong></td>
                            <td class="text-center text-bold text-blue">65 kits</td>
                            <td>Hace 18 min (Folio #0988)</td>
                            <td><span class="badge-status idle">● Inactivo</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <script src="public/js/encargado.js"></script>
</body>

</html>