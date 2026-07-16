<?php
session_start();

// Bloque anti-caché: Obliga al navegador a consultar al servidor siempre
header("Cache-Control: no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Solo permitimos el acceso al Encargado / Supervisor
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Encargado') {
    header('Location: index.php');
    exit();
}

//  CONEXIÓN MAESTRA CON TU ARCHIVO CENTRAL DE DB
require_once 'config/db.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Supervisión (Encargado) - Proyecto Deportivo</title>
    <link rel="stylesheet" href="public/css/styles.css">
    <link rel="icon" href="public/img/logo.png" type="image/png">
</head>

<body class="bg-light">

    <header class="navbar">
        <div class="navbar-brand">
            <img src="public/img/logo.png" alt="Proyecto Deportivo" class="nav-logo">
            <span>Módulo Encargado (Supervisor)</span>
        </div>
        <div class="navbar-user">
            <span class="user-badge"><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
            <button id="btn-logout" class="btn-logout" onclick="window.location.href='api/logout.php'">Cerrar Sesión</button>
        </div>
    </header>

    <main class="dashboard-container">

        <section class="supervisor-controls">
            <div class="control-box-select">
                <label for="select-evento-activo" class="label-bold">Monitorear Competencia:</label>
                <select id="select-evento-activo" class="form-select" onchange="cargarEstadisticasSupervisor()">
                    <?php
                    // EXTRACCIÓN MAESTRA: Trae los circuitos deportivos directamente de SQL Server
                    $sql_sel = "SELECT id, nombre_evento FROM tbl_eventos ORDER BY id DESC";
                    $stmt_sel = sqlsrv_query($conn, $sql_sel);

                    if ($stmt_sel !== false) {
                        $index = 0;
                        while ($ev = sqlsrv_fetch_array($stmt_sel, SQLSRV_FETCH_ASSOC)) {
                            $index++;
                            // Marcamos por defecto el evento más reciente como activo al entrar
                            $selected = ($index === 1) ? 'selected' : '';
                            echo "<option value='" . $ev['id'] . "' " . $selected . ">" . htmlspecialchars($ev['nombre_evento']) . "</option>";
                        }
                        if ($index === 0) {
                            echo "<option value=''>-- No hay competencias creadas --</option>";
                        }
                    } else {
                        echo "<option value=''>Error al conectar con SQL Server</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="refresh-indicator">
                <span class="status-dot pulse"></span> Actualizando automáticamente en vivo
            </div>
        </section>

        <section class="metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">

            <div class="metric-card card-blue">
                <h3>Total Inscritos</h3>
                <p id="total-inscritos">-</p>
                <span class="card-footer-text">Competidores totales</span>
            </div>

            <div class="metric-card card-green">
                <h3>Kits Entregados</h3>
                <p id="total-entregados">-</p>
                <span id="txt-porcentaje-encargado" class="card-footer-text">Calculando...</span>
            </div>

            <div class="metric-card card-yellow">
                <h3>Kits Pendientes</h3>
                <p id="total-pendientes">-</p>
                <span class="card-footer-text">En inventario de mesas</span>
            </div>

            <div class="metric-card card-purple" style="background-color: #F5F3FF; border-left: 5px solid #7C3AED; color: #5B21B6;">
                <h3 style="color: #6D28D9;">Cambios Realizados</h3>
                <p id="total-cambios" style="color: #4C1D95;">-</p>
                <span class="card-footer-text" style="color: #7C3AED;">Incidencias textiles / reasignados</span>
            </div>

        </section>

        <section class="admin-card full-width" style="margin-bottom: 2rem;">
            <div class="card-header-box">
                <div>
                    <h2>Mesa de Incidencias / Reasignación de Categoría</h2>
                    <p class="section-desc">Busque un competidor por su folio o nombre para corregir errores de registro o cambios de bloque de salida.</p>
                </div>
            </div>
            <div class="search-box-container" style="margin-top: 1rem;">
                <input type="text" id="search-supervisor" oninput="buscarAtletaSupervisor()" placeholder="🔍 Ingrese Folio o Nombre del atleta a corregir..." style="width: 100%; padding: 0.9rem; border-radius: 0.5rem; border: 2px solid #CBD5E1; font-size: 1.05rem; box-sizing: border-box;">
            </div>
            <div id="resultados-supervisor" class="contenedor-tarjetas" style="margin-top: 1rem; display: grid; gap: 1rem;">
                <p class="text-center text-muted" style="grid-column: 1/-1;">Escriba los datos del atleta arriba para abrir las herramientas de edición.</p>
            </div>
        </section>

        <section class="admin-card full-width">
            <div class="card-header-box">
                <div>
                    <h2>Productividad del Staff en Campo</h2>
                    <p class="section-desc">Monitoree cuántos paquetes ha validado individualmente cada operador en las mesas.</p>
                </div>
                <span class="table-summary-badge" id="lbl-mesas-activas">Monitoreando...</span>
            </div>

            <div class="table-responsive">
                <table class="admin-table data-table-centered">
                    <thead>
                        <tr>
                            <th>Mesa</th>
                            <th>Nombre del Operador (Staff)</th>
                            <th class="text-center">Kits Validados con Firma</th>
                            <th>Estatus Operativo</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-productividad-body">
                        <tr>
                            <td colspan="4" class="text-center text-muted">Cargando datos de productividad del equipo...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <div id="modal-supervisor" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 450px; box-sizing: border-box;">
            <span class="close-modal" onclick="cerrarModalSupervisor()">&times;</span>
            <h2>Reasignar Categoría</h2>
            <p style="margin-bottom: 1.5rem;">Atleta Folio: <b id="txt-folio-sup">-</b></p>

            <div class="form-group" style="text-align: left; margin-bottom: 1.5rem;">
                <label for="select-categoria-sup" style="font-weight: 700; display: block; margin-bottom: 0.5rem; color: #1E293B;">
                    Nueva Categoría del Competidor:
                </label>
                <!--Dejamos el select vacío; JavaScript se encargará de inyectar las opciones reales -->
                <select id="select-categoria-sup" style="width: 100%; padding: 0.8rem; border: 2px solid #CBD5E1; border-radius: 0.5rem; background-color: white; font-family: inherit; font-size: 1rem;">
                    <option value="">Cargando categorías...</option>
                </select>
            </div>

            <div class="modal-actions" style="display: flex; gap: 1rem; justify-content: center; width: 100%;">
                <button type="button" onclick="cerrarModalSupervisor()" style="background-color: #F1F5F9; color: #334155; border: 1px solid #CBD5E1; padding: 0.6rem 2rem; border-radius: 0.5rem; cursor: pointer; font-weight: 600;">Cancelar</button>
                <button type="button" onclick="guardarCambioCategoria()" style="background-color: #D97706; color: white; border: none; padding: 0.6rem 2rem; border-radius: 0.5rem; font-weight: 700; cursor: pointer;">Guardar Modificación</button>
            </div>
        </div>
    </div>

    <script src="public/js/encargado.js?v=1.4"></script>
</body>

</html>