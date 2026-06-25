<?php
session_start();

// Bloque anti-caché
header("Cache-Control: no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    header('Location: index.php');
    exit();
}

require_once 'config/db.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Proyecto Deportivo</title>
    <link rel="stylesheet" href="public/css/styles.css">
    <link rel="icon" href="public/img/logo.png" type="image/png">
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

        <div class="dashboard-header">
            <h1>Panel de Control Global</h1>
            <p>Gestione las competencias, usuarios e importación masiva de datos en SQL Server 2014.</p>
        </div>

        <div class="admin-grid" style="display: block; width: 100%;">

            <section class="admin-card full-width">
                <div class="card-header-box">
                    <div>
                        <h2>Módulo de Competencias Activas</h2>
                        <p class="section-desc">Monitoreo y administración de circuitos deportivos vigentes en la plataforma.</p>
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="abrirModalNuevoEvento()" style="background-color: #1E40AF; color: white; padding: 0.6rem 1.2rem; font-weight: 700; border-radius: 6px; border: none; cursor: pointer;">
                        + Nuevo Evento
                    </button>
                </div>

                <div class="table-responsive" style="margin-top: 1rem;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Evento</th>
                                <th>Fecha</th>
                                <th>Disciplina</th>
                                <th>Acciones Logísticas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Modificamos el SELECT para jalar los stocks del inventario de prendas
                            $sql_eventos = "SELECT id, nombre_evento, fecha, disciplina, stock_xs, stock_ch, stock_m, stock_g, stock_xg, stock_2xl FROM tbl_eventos ORDER BY id DESC";
                            $stmt_eventos = sqlsrv_query($conn, $sql_eventos);

                            if ($stmt_eventos === false) {
                                echo "<tr><td colspan='5' class='text-center text-danger'>Error al conectar con las tablas de SQL Server.</td></tr>";
                            } else {
                                $contador = 0;
                                while ($row = sqlsrv_fetch_array($stmt_eventos, SQLSRV_FETCH_ASSOC)) {
                                    $contador++;

                                    $fecha_formateada = "";
                                    $fecha_raw = "";
                                    if ($row['fecha'] instanceof DateTime) {
                                        $fecha_formateada = $row['fecha']->format('d/m/Y');
                                        $fecha_raw = $row['fecha']->format('Y-m-d');
                                    } else {
                                        $fecha_formateada = $row['fecha'];
                                        $fecha_raw = $row['fecha'];
                                    }

                                    echo "<tr>";
                                    echo "<td><strong>" . str_pad($row['id'], 2, "0", STR_PAD_LEFT) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($row['nombre_evento']) . "</td>";
                                    echo "<td>" . $fecha_formateada . "</td>";
                                    echo "<td><span class='table-badge-mesa'>" . htmlspecialchars($row['disciplina']) . "</span></td>";
                                    echo "<td>";
                                    echo "<button class='btn-action view' onclick=\"window.location.href='admin_evento.php?id=" . $row['id'] . "'\" title='Ver Gráficas del Evento'>📊 Ver Gráficas</button>";

                                    // Pasamos TODOS los stocks al onclick de abrirModalEditar
                                    echo "<button class='btn-action edit' style='margin-left: 0.5rem;' onclick=\"abrirModalEditar(" . $row['id'] . ", '" . addslashes($row['nombre_evento']) . "', '" . $fecha_raw . "', '" . $row['disciplina'] . "', " . $row['stock_xs'] . ", " . $row['stock_ch'] . ", " . $row['stock_m'] . ", " . $row['stock_g'] . ", " . $row['stock_xg'] . ", " . $row['stock_2xl'] . ")\">📝</button>";
                                    echo "<button class='btn-action delete' style='margin-left: 0.5rem;' onclick=\"eliminarEvento(" . $row['id'] . ", '" . addslashes($row['nombre_evento']) . "')\">🗑️</button>";

                                    echo "</td>";
                                    echo "</tr>";
                                }

                                if ($contador === 0) {
                                    echo "<tr><td colspan='5' class='text-center text-muted'>Ningún circuito registrado aún. Registre uno nuevo arriba.</td></tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>

        <div class="admin-grid" style="display: block; width: 100%; margin-top: 2.5rem;">
            <section class="admin-card full-width">
                <div class="card-header-box">
                    <div>
                        <h2>Módulo de Supervisores de Control (Encargados)</h2>
                        <p class="section-desc">Gestione las cuentas del personal encargado de abrir competencias y monitorear estadísticas en tiempo real.</p>
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="abrirModalNuevoEncargado()" style="background-color: #1E40AF; color: white; padding: 0.6rem 1.2rem; font-weight: 700; border-radius: 6px; border: none; cursor: pointer;">
                        + Registrar Encargado
                    </button>
                </div>

                <div class="table-responsive" style="margin-top: 1rem;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Supervisor</th>
                                <th>Correo de Acceso</th>
                                <th>Rol Operativo</th>
                                <th>Acciones de Cuenta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql_enc = "SELECT id, nombre, correo FROM tbl_usuarios WHERE rol = 'Encargado' ORDER BY id DESC";
                            $stmt_enc = sqlsrv_query($conn, $sql_enc);

                            if ($stmt_enc === false) {
                                $errors = sqlsrv_errors();
                                $errorMaster = isset($errors[0]['message']) ? $errors[0]['message'] : 'Error de consistencia';
                                echo "<tr><td colspan='5' class='text-center text-danger'><b>SQL Server dice:</b> " . htmlspecialchars($errorMaster) . "</td></tr>";
                            } else {
                                $cont_enc = 0;
                                while ($e = sqlsrv_fetch_array($stmt_enc, SQLSRV_FETCH_ASSOC)) {
                                    $cont_enc++;
                                    echo "<tr>";
                                    echo "<td><strong>" . str_pad($e['id'], 2, "0", STR_PAD_LEFT) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($e['nombre']) . "</td>";
                                    echo "<td><code>" . htmlspecialchars($e['correo']) . "</code></td>";
                                    echo "<td><span class='table-badge-mesa' style='background-color: #EFF6FF; color: #1E40AF; font-weight: bold;'>Monitor Central</span></td>";
                                    echo "<td>";
                                    echo "<button class='btn-action edit' onclick=\"abrirModalEditarEncargado(" . $e['id'] . ", '" . addslashes($e['nombre']) . "', '" . addslashes($e['correo']) . "')\">📝 Editar</button>";
                                    echo "<button class='btn-action delete' style='margin-left: 0.5rem;' onclick=\"eliminarEncargado(" . $e['id'] . ", '" . addslashes($e['nombre']) . "')\">🗑️</button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }

                                if ($cont_enc === 0) {
                                    echo "<tr><td colspan='5' class='text-center text-muted'>No hay encargados registrados. Presione '+ Registrar Encargado'.</td></tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>


        <div id="modal-encargado" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 480px; box-sizing: border-box;">
                <span class="close-modal" onclick="cerrarModalEncargado()">&times;</span>
                <h2 id="enc-modal-titulo" style="color: #0F172A; border-bottom: 2px solid #E2E8F0; padding-bottom: 0.5rem; margin-top: 0;">👤 Registrar Encargado</h2>
                <p class="section-desc" style="margin-bottom: 1.5rem;">Gestione credenciales para el acceso al Monitor de Supervisión.</p>

                <form id="form-encargado" onsubmit="guardarEncargado(event)" style="text-align: left; display: grid; gap: 1.25rem;">
                    <input type="hidden" id="enc_id" name="enc_id">

                    <div class="form-group">
                        <label style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Nombre Completo:</label>
                        <input type="text" id="enc_nombre" name="enc_nombre" required style="width: 100%; padding: 0.65rem; border: 1px solid #CBD5E1; border-radius: 6px; box-sizing: border-box;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Correo Acceso:</label>
                            <input type="email" id="enc_correo" name="enc_correo" required style="width: 100%; padding: 0.65rem; border: 1px solid #CBD5E1; border-radius: 6px; box-sizing: border-box;">
                        </div>
                        <div class="form-group">
                            <label style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Contraseña:</label>
                            <input type="password" id="enc_password" name="enc_password" placeholder="Escriba la clave..." style="width: 100%; padding: 0.65rem; border: 1px solid #CBD5E1; border-radius: 6px; box-sizing: border-box;">
                        </div>
                    </div>

                    <div class="modal-actions" style="display: flex; gap: 1rem; justify-content: center; width: 100%; margin-top: 0.5rem;">
                        <button type="button" onclick="cerrarModalEncargado()" style="background-color: #F1F5F9; color: #334155; border: 1px solid #CBD5E1; padding: 0.65rem 2rem; border-radius: 6px; font-weight: 600; cursor: pointer;">Cancelar</button>
                        <button type="submit" id="btn-submit-encargado" style="background-color: #1E40AF; color: white; border: none; padding: 0.65rem 2rem; border-radius: 6px; font-weight: 700; cursor: pointer; flex-grow: 1;">Guardar Supervisor</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            const modalEncargado = document.getElementById('modal-encargado');

            window.abrirModalNuevoEncargado = () => {
                document.getElementById('form-encargado').reset();
                document.getElementById('enc_id').value = "0";
                document.getElementById('enc-modal-titulo').innerText = "👤 Registrar Encargado";
                document.getElementById('enc_password').placeholder = "Escriba la clave...";
                document.getElementById('enc_password').required = true;
                if (modalEncargado) modalEncargado.style.display = 'flex';
            };

            window.abrirModalEditarEncargado = (id, nombre, correo) => {
                document.getElementById('form-encargado').reset();
                document.getElementById('enc_id').value = id;
                document.getElementById('enc_nombre').value = nombre;
                document.getElementById('enc_correo').value = correo;

                document.getElementById('enc-modal-titulo').innerText = "Modificar Perfil Supervisor";
                document.getElementById('enc_password').placeholder = "Dejar en blanco para conservar";
                document.getElementById('enc_password').required = false;
                if (modalEncargado) modalEncargado.style.display = 'flex';
            };

            window.cerrarModalEncargado = () => {
                if (modalEncargado) modalEncargado.style.display = 'none';
            };

            async function guardarEncargado(event) {
                event.preventDefault();
                const btn = document.getElementById('btn-submit-encargado');
                btn.disabled = true;
                btn.innerText = "Sincronizando...";

                try {
                    const response = await fetch('api/guardar_encargado.php', {
                        method: 'POST',
                        body: new FormData(document.getElementById('form-encargado'))
                    });
                    const res = await response.json();
                    if (res.success) {
                        window.location.reload();
                    } else {
                        alert("Error: " + res.message);
                        btn.disabled = false;
                        btn.innerText = "Guardar Supervisor";
                    }
                } catch (error) {
                    console.error(error);
                    alert("Error de red.");
                    btn.disabled = false;
                }
            }

            window.eliminarEncargado = async (id, nombre) => {
                const seguro = confirm(`¿Desea dar de baja la cuenta del Supervisor: "${nombre}"?\nYa no tendrá acceso al Monitor General.`);
                if (!seguro) return;

                try {
                    const response = await fetch('api/eliminar_encargado.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: id
                        })
                    });
                    const res = await response.json();
                    if (res.success) {
                        window.location.reload();
                    } else {
                        alert("Error: " + res.message);
                    }
                } catch (error) {
                    console.error(error);
                    alert("Error de red.");
                }
            };
        </script>

        <div class="admin-grid" style="display: block; width: 100%; margin-top: 2.5rem;">
            <section class="admin-card full-width">
                <div class="card-header-box">
                    <div>
                        <h2>Módulo de Personal en Campo (Staff)</h2>
                        <p class="section-desc">Administre las credenciales de los operadores de mesa y asígnelos al circuito correspondiente.</p>
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="abrirModalNuevoStaff()" style="background-color: #1E40AF; color: white; padding: 0.6rem 1.2rem; font-weight: 700; border-radius: 6px; border: none; cursor: pointer;">
                        + Registrar Operador
                    </button>
                </div>

                <div class="table-responsive" style="margin-top: 1rem;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Operador</th>
                                <th>Correo de Acceso</th>
                                <th>Competencia Asignada</th>
                                <th>Acciones de Cuenta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            //CONSULTA CORREGIDA: Apunta a u.correo de acuerdo a tu esquema
                            $sql_staff = "SELECT u.id, u.nombre, u.correo, u.evento_asignado_id, e.nombre_evento 
                                            FROM tbl_usuarios u
                                            LEFT JOIN tbl_eventos e ON u.evento_asignado_id = e.id
                                            WHERE u.rol = 'Staff'
                                            ORDER BY u.id DESC";
                            $stmt_staff = sqlsrv_query($conn, $sql_staff);

                            if ($stmt_staff === false) {
                                $errors = sqlsrv_errors();
                                $errorMaster = isset($errors[0]['message']) ? $errors[0]['message'] : 'Error de consistencia';
                                echo "<tr><td colspan='5' class='text-center text-danger'><b>SQL Server dice:</b> " . htmlspecialchars($errorMaster) . "</td></tr>";
                            } else {
                                $cont_staff = 0;
                                while ($s = sqlsrv_fetch_array($stmt_staff, SQLSRV_FETCH_ASSOC)) {
                                    $cont_staff++;
                                    $evento_texto = $s['nombre_evento'] ? htmlspecialchars($s['nombre_evento']) : 'Sin Competencia Asignada';
                                    $badge_estilo = $s['nombre_evento'] ? "style='background-color: #EEF2F6; color: #1E293B;'" : "style='background-color: #FEF2F2; color: #991B1B; font-weight: bold;'";

                                    echo "<tr>";
                                    echo "<td><strong>" . str_pad($s['id'], 2, "0", STR_PAD_LEFT) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($s['nombre']) . "</td>";
                                    // Cambiado a $s['correo']
                                    echo "<td><code>" . htmlspecialchars($s['correo']) . "</code></td>";
                                    echo "<td><span class='table-badge-mesa' {$badge_estilo}>" . $evento_texto . "</span></td>";
                                    echo "<td>";
                                    // En el onclick pasamos $s['correo']
                                    echo "<button class='btn-action edit' onclick=\"abrirModalEditarStaff(" . $s['id'] . ", '" . addslashes($s['nombre']) . "', '" . addslashes($s['correo']) . "', " . intval($s['evento_asignado_id']) . ")\">📝 Editar</button>";
                                    echo "<button class='btn-action delete' style='margin-left: 0.5rem;' onclick=\"eliminarStaff(" . $s['id'] . ", '" . addslashes($s['nombre']) . "')\">🗑️</button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }

                                if ($cont_staff === 0) {
                                    echo "<tr><td colspan='5' class='text-center text-muted'>No hay personal registrado. Presione '+ Registrar Operador'.</td></tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

    </main>
    <div id="modal-staff" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 480px; box-sizing: border-box;">
            <span class="close-modal" onclick="cerrarModalStaff()">&times;</span>
            <h2 id="staff-modal-titulo" style="color: #0F172A; border-bottom: 2px solid #E2E8F0; padding-bottom: 0.5rem; margin-top: 0;">👤 Registrar Operador</h2>
            <p class="section-desc" style="margin-bottom: 1.5rem;">Gestione el perfil del personal en campo y defina su mesa operativa.</p>

            <form id="form-staff" onsubmit="guardarStaff(event)" style="text-align: left; display: grid; gap: 1.25rem;">
                <input type="hidden" id="staff_id" name="staff_id">

                <div class="form-group">
                    <label style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Nombre Completo:</label>
                    <input type="text" id="staff_nombre" name="staff_nombre" required style="width: 100%; padding: 0.65rem; border: 1px solid #CBD5E1; border-radius: 6px; box-sizing: border-box;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Correo de Acceso:</label>
                        <input type="text" id="staff_usuario" name="staff_usuario" required style="width: 100%; padding: 0.65rem; border: 1px solid #CBD5E1; border-radius: 6px; box-sizing: border-box;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Contraseña:</label>
                        <input type="password" id="staff_password" name="staff_password" placeholder="Mismo password" style="width: 100%; padding: 0.65rem; border: 1px solid #CBD5E1; border-radius: 6px; box-sizing: border-box;">
                    </div>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Competencia por Asignar:</label>
                    <select id="staff_evento_id" name="staff_evento_id" style="width: 100%; padding: 0.65rem; border: 1px solid #CBD5E1; border-radius: 6px; background: white; box-sizing: border-box;">
                        <option value="0">-- Ninguno / En Espera de Carrera --</option>
                        <?php
                        // Listado dinámico de eventos para el selector del Staff
                        $sql_ev_list = "SELECT id, nombre_evento FROM tbl_eventos ORDER BY id DESC";
                        $stmt_ev_list = sqlsrv_query($conn, $sql_ev_list);
                        if ($stmt_ev_list !== false) {
                            while ($ev = sqlsrv_fetch_array($stmt_ev_list, SQLSRV_FETCH_ASSOC)) {
                                echo "<option value='" . $ev['id'] . "'>" . htmlspecialchars($ev['nombre_evento']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="modal-actions" style="display: flex; gap: 1rem; justify-content: center; width: 100%; margin-top: 0.5rem;">
                    <button type="button" onclick="cerrarModalStaff()" style="background-color: #F1F5F9; color: #334155; border: 1px solid #CBD5E1; padding: 0.65rem 2rem; border-radius: 6px; font-weight: 600; cursor: pointer;">Cancelar</button>
                    <button type="submit" id="btn-submit-staff" style="background-color: #059669; color: white; border: none; padding: 0.65rem 2rem; border-radius: 6px; font-weight: 700; cursor: pointer; flex-grow: 1;">💾 Guardar Personal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modalStaff = document.getElementById('modal-staff');

        window.abrirModalNuevoStaff = () => {
            document.getElementById('form-staff').reset();
            document.getElementById('staff_id').value = "0";
            document.getElementById('staff-modal-titulo').innerText = "👤 Registrar Operador";
            document.getElementById('staff_password').placeholder = "Escriba la clave...";
            document.getElementById('staff_password').required = true;
            if (modalStaff) modalStaff.style.display = 'flex';
        };

        window.abrirModalEditarStaff = (id, nombre, usuario, eventoId) => {
            document.getElementById('form-staff').reset();
            document.getElementById('staff_id').value = id;
            document.getElementById('staff_nombre').value = nombre;
            document.getElementById('staff_usuario').value = usuario;
            document.getElementById('staff_evento_id').value = eventoId ? eventoId : 0;

            document.getElementById('staff-modal-titulo').innerText = "Modificar Perfil Staff";
            document.getElementById('staff_password').placeholder = "Dejar en blanco para no cambiar";
            document.getElementById('staff_password').required = false;
            if (modalStaff) modalStaff.style.display = 'flex';
        };

        window.cerrarModalStaff = () => {
            if (modalStaff) modalStaff.style.display = 'none';
        };

        async function guardarStaff(event) {
            event.preventDefault();
            const btn = document.getElementById('btn-submit-staff');
            btn.disabled = true;
            btn.innerText = "Sincronizando...";

            try {
                const response = await fetch('api/guardar_staff.php', {
                    method: 'POST',
                    body: new FormData(document.getElementById('form-staff'))
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert("Error: " + res.message);
                    btn.disabled = false;
                    btn.innerText = "Guardar Personal";
                }
            } catch (error) {
                console.error(error);
                alert("Error de red.");
                btn.disabled = false;
            }
        }

        window.eliminarStaff = async (id, nombre) => {
            const seguro = confirm(`¿Desea eliminar la cuenta de: "${nombre}"?\nEl operador ya no podrá acceder a las mesas de validación.`);
            if (!seguro) return;

            try {
                const response = await fetch('api/eliminar_staff.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id
                    })
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert("Error: " + res.message);
                }
            } catch (error) {
                console.error(error);
                alert("Error de red.");
            }
        };
    </script>
    </main>

    <div id="modal-registro-evento" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 520px; box-sizing: border-box;">
            <span class="close-modal" onclick="cerrarModalNuevoEvento()">&times;</span>
            <h2 style="color: #0F172A; border-bottom: 2px solid #E2E8F0; padding-bottom: 0.5rem; margin-top: 0;">🏆 Registrar Competencia</h2>
            <p class="section-desc" style="margin-bottom: 1.5rem;">Ingrese los parámetros del circuito y cargue su padrón para darlo de alta.</p>

            <form id="form-unificado-evento" onsubmit="guardarEventoYPadron(event)" enctype="multipart/form-data" style="text-align: left; display: grid; gap: 1.25rem;">
                <div class="form-group">
                    <label for="nombre_evento" style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Nombre del Evento:</label>
                    <input type="text" id="nombre_evento" name="nombre_evento" placeholder="Ej. Carrera Atlética Morelia 10K 2026" required style="width: 100%; padding: 0.65rem; border: 1px solid #CBD5E1; border-radius: 6px; font-family: inherit; box-sizing: border-box;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="fecha_evento" style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Fecha:</label>
                        <input type="date" id="fecha_evento" name="fecha_evento" required style="width: 100%; padding: 0.6rem; border: 1px solid #CBD5E1; border-radius: 6px; box-sizing: border-box;">
                    </div>
                    <div class="form-group">
                        <label for="disciplina_evento" style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Disciplina:</label>
                        <select id="disciplina_evento" name="disciplina_evento" required style="width: 100%; padding: 0.6rem; border: 1px solid #CBD5E1; border-radius: 6px; background: white; box-sizing: border-box;">
                            <option value="Carrera">Carrera 🏃‍♂️</option>
                            <option value="Triatlón">Triatlón 🏊‍♂️🚴‍♂️</option>
                            <option value="Ciclismo">Ciclismo 🚴‍♂️</option>
                        </select>
                    </div>
                </div>
                <div style="background-color: #FFFBEB; border: 2px dashed #F59E0B; padding: 1.25rem; border-radius: 0.5rem; text-align: center; box-sizing: border-box;">
                    <label for="file_csv" style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #B45309; font-size: 0.95rem;">Adjuntar Lista (.csv):</label>
                    <input type="file" id="file_csv" name="file_csv" accept=".csv" required style="width: 100%; padding: 0.4rem; background: white; border: 1px solid #FDE68A; border-radius: 6px; font-size: 0.85rem; box-sizing: border-box;">
                </div>
                <div class="modal-actions" style="display: flex; gap: 1rem; justify-content: center; width: 100%;">
                    <button type="button" class="btn" onclick="cerrarModalNuevoEvento()" style="background-color: #F1F5F9; color: #334155; border: 1px solid #CBD5E1; padding: 0.65rem 2rem; border-radius: 6px; font-weight: 600; cursor: pointer;">Cancelar</button>
                    <button type="submit" id="btn-submit-unificado" style="background-color: #1E40AF; color: white; border: none; padding: 0.65rem 2rem; border-radius: 6px; font-weight: 700; cursor: pointer; flex-grow: 1;">🚀 Guardar e Importar Padrón</button>
                </div>
            </form>
            <div id="resultado-unificado-box" style="display: none; margin-top: 1.25rem; padding: 1rem; border-radius: 6px; font-weight: 600; text-align: left; font-size: 0.9rem;"></div>
        </div>
    </div>

    <div id="modal-editar-evento" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 540px; box-sizing: border-box;">
            <span class="close-modal" onclick="cerrarModalEditar()">&times;</span>
            <h2 style="color: #0F172A; border-bottom: 2px solid #E2E8F0; padding-bottom: 0.5rem; margin-top: 0;">Modificar Competencia</h2>
            <p class="section-desc" style="margin-bottom: 1.5rem;">Modifique los valores de cabecera y configure el stock total de playeras disponibles.</p>

            <form id="form-editar-evento" onsubmit="guardarEdicionEvento(event)" style="text-align: left; display: grid; gap: 1.25rem;">
                <input type="hidden" id="edit_id" name="edit_id">

                <div class="form-group">
                    <label for="edit_nombre" style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Nombre del Evento:</label>
                    <input type="text" id="edit_nombre" name="edit_nombre" required style="width: 100%; padding: 0.65rem; border: 1px solid #CBD5E1; border-radius: 6px; font-family: inherit; box-sizing: border-box;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="edit_fecha" style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Fecha:</label>
                        <input type="date" id="edit_fecha" name="edit_fecha" required style="width: 100%; padding: 0.6rem; border: 1px solid #CBD5E1; border-radius: 6px; box-sizing: border-box;">
                    </div>
                    <div class="form-group">
                        <label for="edit_disciplina" style="font-weight: 700; display: block; margin-bottom: 0.4rem; color: #334155;">Disciplina:</label>
                        <select id="edit_disciplina" name="edit_disciplina" required style="width: 100%; padding: 0.6rem; border: 1px solid #CBD5E1; border-radius: 6px; background: white; box-sizing: border-box;">
                            <option value="Carrera">Carrera 🏃‍♂️</option>
                            <option value="Triatlón">Triatlón 🏊‍♂️</option>
                            <option value="Ciclismo">Ciclismo 🚴‍♂️</option>
                        </select>
                    </div>
                </div>

                <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; padding: 1.25rem; border-radius: 8px;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1rem; color: #1E3A8A; font-weight: 700;">👕 Configuración de Stock Total por Talla</h3>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                        <div class="form-group">
                            <label style="font-size: 0.85rem; font-weight:700;">Talla XS:</label>
                            <input type="number" id="edit_stock_xs" name="edit_stock_xs" min="0" required style="width:100%; padding:0.4rem; border:1px solid #CBD5E1; border-radius:4px;">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.85rem; font-weight:700;">Talla CH:</label>
                            <input type="number" id="edit_stock_ch" name="edit_stock_ch" min="0" required style="width:100%; padding:0.4rem; border:1px solid #CBD5E1; border-radius:4px;">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.85rem; font-weight:700;">Talla M:</label>
                            <input type="number" id="edit_stock_m" name="edit_stock_m" min="0" required style="width:100%; padding:0.4rem; border:1px solid #CBD5E1; border-radius:4px;">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.85rem; font-weight:700;">Talla G:</label>
                            <input type="number" id="edit_stock_g" name="edit_stock_g" min="0" required style="width:100%; padding:0.4rem; border:1px solid #CBD5E1; border-radius:4px;">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.85rem; font-weight:700;">Talla XG:</label>
                            <input type="number" id="edit_stock_xg" name="edit_stock_xg" min="0" required style="width:100%; padding:0.4rem; border:1px solid #CBD5E1; border-radius:4px;">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.85rem; font-weight:700;">Talla 2XL:</label>
                            <input type="number" id="edit_stock_2xl" name="edit_stock_2xl" min="0" required style="width:100%; padding:0.4rem; border:1px solid #CBD5E1; border-radius:4px;">
                        </div>
                    </div>
                </div>

                <div class="modal-actions" style="display: flex; gap: 1rem; justify-content: center; width: 100%; margin-top: 0.5rem;">
                    <button type="button" onclick="cerrarModalEditar()" style="background-color: #F1F5F9; color: #334155; border: 1px solid #CBD5E1; padding: 0.65rem 2rem; border-radius: 6px; font-weight: 600; cursor: pointer;">Cancelar</button>
                    <button type="submit" id="btn-submit-editar" style="background-color: #D97706; color: white; border: none; padding: 0.65rem 2rem; border-radius: 6px; font-weight: 700; cursor: pointer; flex-grow: 1;">💾 Aplicar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modalNuevo = document.getElementById('modal-registro-evento');
        const modalEditar = document.getElementById('modal-editar-evento');

        window.abrirModalNuevoEvento = () => {
            if (modalNuevo) modalNuevo.style.display = 'flex';
        };
        window.cerrarModalNuevoEvento = () => {
            if (modalNuevo) {
                modalNuevo.style.display = 'none';
                document.getElementById('form-unificado-evento').reset();
                document.getElementById('resultado-unificado-box').style.display = 'none';
            }
        };

        // --- MANEJO DEL MODAL DE EDICIÓN CON LOS NUEVOS CAMPOS TEXTILES ---
        window.abrirModalEditar = (id, nombre, fecha, disciplina, s_xs, s_ch, s_m, s_g, s_xg, s_2xl) => {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_fecha').value = fecha;
            document.getElementById('edit_disciplina').value = disciplina;

            // Inyectamos las cantidades de stock actuales en las cajas de texto del modal
            document.getElementById('edit_stock_xs').value = s_xs;
            document.getElementById('edit_stock_ch').value = s_ch;
            document.getElementById('edit_stock_m').value = s_m;
            document.getElementById('edit_stock_g').value = s_g;
            document.getElementById('edit_stock_xg').value = s_xg;
            document.getElementById('edit_stock_2xl').value = s_2xl;

            if (modalEditar) modalEditar.style.display = 'flex';
        };

        window.cerrarModalEditar = () => {
            if (modalEditar) modalEditar.style.display = 'none';
        };

        async function guardarEdicionEvento(event) {
            event.preventDefault();
            const btn = document.getElementById('btn-submit-editar');
            btn.disabled = true;
            btn.innerText = "Guardando...";

            try {
                const response = await fetch('api/editar_evento.php', {
                    method: 'POST',
                    body: new FormData(document.getElementById('form-editar-evento'))
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert("Error: " + res.message);
                    btn.disabled = false;
                    btn.innerText = " Aplicar Cambios";
                }
            } catch (error) {
                console.error(error);
                alert("Error de red.");
                btn.disabled = false;
            }
        };

        // --- MANEJO DE ELIMINACIÓN ---
        window.eliminarEvento = async (id, nombre) => {
            const seguro = confirm(`¿ESTÁS ABSOLUTAMENTE SEGURO DE ELIMINAR EL EVENTO: "${nombre}"?\n\nEsta acción borrará de forma PERMANENTE la competencia, todos los competidores del padrón y las firmas de los kits entregados.`);
            if (!seguro) return;

            try {
                const response = await fetch('api/eliminar_evento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id
                    })
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert("Error: " + res.message);
                }
            } catch (error) {
                console.error(error);
                alert("Error al intentar comunicarse con la API.");
            }
        };

        // --- GUARDAR NUEVO ---
        async function guardarEventoYPadron(event) {
            event.preventDefault();
            const form = document.getElementById('form-unificado-evento');
            const btnSubmit = document.getElementById('btn-submit-unificado');
            const resultadoBox = document.getElementById('resultado-unificado-box');
            const formData = new FormData(form);
            btnSubmit.disabled = true;
            btnSubmit.innerText = "Insertando circuito e indexando competidores...";
            resultadoBox.style.display = "none";
            try {
                const response = await fetch('api/crear_evento.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                resultadoBox.style.display = "block";
                if (res.success) {
                    resultadoBox.style.backgroundColor = "#DCFCE7";
                    resultadoBox.style.color = "#15803D";
                    resultadoBox.style.border = "1px solid #BBF7D0";
                    resultadoBox.innerHTML = `<b>¡Alta Exitosa!</b><br>${res.message}<br>• Competidores inyectados: <b>${res.inserted_athletes}</b>`;
                    form.reset();
                    setTimeout(() => {
                        window.location.reload();
                    }, 2500);
                } else {
                    resultadoBox.style.backgroundColor = "#FEE2E2";
                    resultadoBox.style.color = "#991B1B";
                    resultadoBox.style.border = "1px solid #FCA5A5";
                    resultadoBox.innerHTML = `<b>Error en la transacción:</b> ${res.message}`;
                    btnSubmit.disabled = false;
                    btnSubmit.innerText = " Guardar e Importar Padrón";
                }
            } catch (error) {
                console.error(error);
                btnSubmit.disabled = false;
            }
        };
    </script>

</body>

</html>