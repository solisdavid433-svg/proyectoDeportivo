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
    <title>Panel de Administración - Proyecto Deportivo</title>
    <link rel="stylesheet" href="public/css/styles.css">
</head>

<body>

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

        <div class="dashboard-header">
            <h1>Panel de Control Global</h1>
            <p>Gestione las competencias, usuarios e importación masiva de datos en SQL Server 2014.</p>
        </div>

        <div class="admin-grid">

            <section class="admin-card">
                <div class="card-header-box">
                    <h2>Módulo de Competencias</h2>
                    <button class="btn btn-secondary btn-sm" id="btn-modal-evento">+ Nuevo Evento</button>
                </div>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Evento</th>
                                <th>Fecha</th>
                                <th>Disciplina</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>01</strong></td>
                                <td>Carrera Atlética Morelia 10K 2026</td>
                                <td>15/08/2026</td>
                                <td>Carrera</td>
                                <td>
                                    <button class="btn-action view" onclick="window.location.href='admin_evento.php'" title="Ver Gráficas del Evento">📊 Ver Gráficas</button>
                                    <button class="btn-action edit">📝</button>
                                    <button class="btn-action delete">🗑️</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>02</strong></td>
                                <td>Triatlón Regional Cuitzeo 2026</td>
                                <td>24/09/2026</td>
                                <td>Triatlón</td>
                                <td>
                                    <button class="btn-action view" onclick="window.location.href='admin_evento.php'" title="Ver Gráficas del Evento">📊 Ver Gráficas</button>
                                    <button class="btn-action edit">📝</button>
                                    <button class="btn-action delete">🗑️</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="admin-card">
                <h2>Importación Masiva de Atletas</h2>
                <p class="section-desc">Seleccione la competencia activa y cargue el archivo del sistema de cronometraje.</p>

                <form id="form-csv-upload" class="admin-form" onsubmit="return false;">

                    <div class="form-group">
                        <label for="select-evento-csv">Asignar al Evento:</label>
                        <select id="select-evento-csv" class="form-select" required>
                            <option value="">-- Seleccione un evento activo --</option>
                            <option value="1">Carrera Atlética Morelia 10K 2026</option>
                            <option value="2">Triatlón Regional Cuitzeo 2026</option>
                        </select>
                    </div>

                    <div class="drop-zone" id="drop-zone">
                        <div class="drop-zone-icon">📁</div>
                        <p class="drop-zone-text">Arrastre aquí el archivo .CSV o haga clic para examinar</p>
                        <input type="file" id="file-csv" accept=".csv" class="file-input-hidden">
                    </div>

                    <div id="file-info-box" class="file-info-box hidden">
                        <span class="file-icon">📊</span>
                        <span id="file-name-display" class="file-name">lista_competidores.csv</span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Procesar e Importar Datos (UTF-8)
                    </button>
                </form>
            </section>

        </div>
    </main>

    <script src="public/js/admin.js"></script>
</body>

</html>