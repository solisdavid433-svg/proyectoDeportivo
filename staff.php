<?php

session_start();

// Bloque anti-caché: Obliga al navegador a consultar al servidor siempre
header("Cache-Control: no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Solo permitimos el acceso al Staff
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Staff') {
    header('Location: index.php');
    exit();
}

// Enlace relacional con tu base de datos
require_once 'config/db.php';

// DETECTOR DE CAMBIO EN VIVO: Si el Staff ya confirmó una carrera, verificamos si sigue activa
if (isset($_SESSION['evento_id_staff'])) {
    $sql_check = "SELECT id FROM tbl_eventos WHERE id = ? AND es_activo = 1";
    $stmt_check = sqlsrv_query($conn, $sql_check, array($_SESSION['evento_id_staff']));
    $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);

    // Si el Encargado ya cambió la carrera en la BD, finalizamos esta mesa para forzar re-confirmación
    if (!$row_check) {
        unset($_SESSION['evento_id_staff']);
        header("Location: staff.php");
        exit();
    }
}

// Si el Staff no ha confirmado qué evento va a cubrir hoy, interceptamos con la tarjeta:
if (!isset($_SESSION['evento_id_staff'])) {
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <title>Confirmar Competencia - Staff</title>
        <link rel="stylesheet" href="public/css/styles.css">
        <link rel="icon" href="public/img/logo.png" type="image/png">
    </head>

    <body class="bg-light" style="display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0;">
        <div class="admin-card" style="max-width: 450px; text-align: center; padding: 2.5rem; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <span style="font-size: 3rem;">🏃‍♂️</span>
            <h2 style="margin-top: 1rem; color: #1E3A8A;">Confirmación de Circuito</h2>
            <p class="section-desc">Verifique que esté ingresando a la competencia correcta para la entrega de kits de hoy.</p>

            <?php
            //ALINEACIÓN MAESTRA: Jalamos estrictamente el evento que el Encargado activó globalmente
            $sql_ev = "SELECT id, nombre_evento, disciplina FROM tbl_eventos WHERE es_activo = 1";
            $stmt_ev = sqlsrv_query($conn, $sql_ev);

            if ($stmt_ev === false) {
                echo "<p class='text-danger'>Error de comunicación con SQL Server 2014.</p>";
            } else {
                $row_ev = sqlsrv_fetch_array($stmt_ev, SQLSRV_FETCH_ASSOC);

                // Si el encargado ya activó una carrera en la base de datos:
                if ($row_ev) {
                    $_SESSION['temp_evento_id'] = $row_ev['id']; // Almacenamos el ID temporalmente
            ?>
                    <div style="background-color: #F0FDF4; border: 1px solid #BBF7D0; padding: 1rem; border-radius: 8px; margin: 1.5rem 0; text-align: left;">
                        <span style="font-size: 0.85rem; font-weight: 700; color: #16A34A; text-transform: uppercase; letter-spacing: 0.05em;">Circuito Detectado en Vivo:</span>
                        <h4 style="margin: 0.25rem 0; color: #14532D; font-size: 1.1rem;"><?php echo htmlspecialchars($row_ev['nombre_evento']); ?></h4>
                        <small style="color: #166534;">Disciplina: <b><?php echo htmlspecialchars($row_ev['disciplina']); ?></b></small>
                    </div>

                    <form action="api/confirmar_evento_staff.php" method="POST">
                        <button type="submit" style="width: 100%; background-color: #16A34A; color: white; border: none; padding: 0.85rem; border-radius: 6px; font-weight: 700; font-size: 1rem; cursor: pointer;">
                            ✔️ Confirmar y Abrir Mesa de Entrega
                        </button>
                    </form>
            <?php
                } else {
                    // 🛡️ SALVAGUARDA LOGÍSTICA: Si el encargado aún no elige nada en su panel
                    echo "<div style='background-color: #FFFBEB; border: 1px solid #FDE68A; padding: 1.2rem; border-radius: 8px; margin: 1.5rem 0; text-align: left; color: #92400E;'>";
                    echo "⚠️ <b>Mesa en Espera:</b> El Encargado aún no ha activado ninguna competencia el día de hoy. Por favor, espere un momento a que se aperture el circuito desde el monitor de supervisión.";
                    echo "</div>";
                    echo "<button onclick='window.location.reload()' style='width: 100%; background-color: #64748B; color: white; border: none; padding: 0.75rem; border-radius: 6px; font-weight: 600; cursor: pointer;'>🔄 Volver a verificar</button>";
                }
            }
            ?>
        </div>
    </body>

    </html>
<?php
    exit(); // Frena por completo el renderizado del buscador hasta que exista un evento activo y confirmado
}

//JALAMOS EL NOMBRE PARA EL NAVBAR:
$nombre_evento_actual = "Mesa de Validación";
$sql_name = "SELECT nombre_evento FROM tbl_eventos WHERE id = ?";
$stmt_name = sqlsrv_query($conn, $sql_name, array($_SESSION['evento_id_staff']));
if ($stmt_name && $row_name = sqlsrv_fetch_array($stmt_name, SQLSRV_FETCH_ASSOC)) {
    $nombre_evento_actual = $row_name['nombre_evento'];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesa de Entrega (Staff) - Proyecto Deportivo</title>
    <link rel="stylesheet" href="public/css/styles.css">
</head>

<body class="bg-light">

    <header class="navbar sticky-navbar">
        <div class="navbar-brand">
            <img src="public/img/logo.png" alt="Proyecto Deportivo" class="nav-logo">
            <span class="event-title-active"><?php echo htmlspecialchars($nombre_evento_actual); ?> - Entrega de Kits</span>
        </div>
        <div class="navbar-user">
            <span class="user-badge"><?php echo $_SESSION['usuario_nombre']; ?></span>
            <button id="btn-logout" class="btn-logout" onclick="window.location.href='api/logout.php'">Salir</button>
        </div>
    </header>

    <main class="staff-container">

        <div class="search-box-container" style="margin: 1.5rem 0; display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <input type="text" id="search-input" placeholder="🔍 Ingrese Folio o Nombre completo del atleta..." style="flex-grow: 1; padding: 1rem; border-radius: 0.5rem; border: 2px solid #CBD5E1; font-size: 1.1rem;">

            <button type="button" id="btn-toggle-qr" onclick="toggleQRScanner()" style="background-color: #0F172A; color: white; border: none; padding: 0 1.5rem; border-radius: 0.5rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-size: 1rem;">
                📷 Escanear QR
            </button>
        </div>

        <div id="qr-reader-container" style="display: none; max-width: 400px; margin: 0 auto 1.5rem auto; background-color: #000000; border-radius: 0.75rem; overflow: hidden; border: 3px solid #0F172A; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
            <div id="qr-reader" style="width: 100%;"></div>
        </div>

        <div id="resultados-atletas" class="contenedor-tarjetas">
            <p class="text-center text-muted">Escriba el nombre o folio del atleta para comenzar...</p>
        </div>
    </main>

    <div id="modal-signature-overlay" class="modal-overlay hidden">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Confirmación de Conformidad y Firma</h3>
            </div>

            <div class="modal-body">
                <p class="modal-instruction">Pida al atleta verificar sus datos y plasmar su firma manuscrita en el recuadro blanco.</p>

                <div class="modal-athlete-summary">
                    <p><strong>Competidor:</strong> <span id="modal-text-name">José David Solís Rangel</span></p>
                    <p><strong>Folio:</strong> <span id="modal-text-id">#1024</span></p>
                </div>

                <div class="canvas-signature-wrapper">
                    <canvas id="signature-canvas" width="450" height="200"></canvas>
                    <p class="canvas-caption">Área de captura digital de firma</p>
                </div>
            </div>

            <div class="modal-footer">
                <button id="btn-clear-canvas" class="btn btn-secondary-outline btn-sm">Limpiar Trazo</button>
                <button id="btn-cancel-signature" class="btn btn-secondary-outline btn-sm">Cancelar</button>
                <button id="btn-confirm-delivery" class="btn btn-success">Validar y Firmar</button>
            </div>
        </div>
    </div>

    <!-- MODAL DE FIRMA DIGITAL -->
    <div id="modal-firma" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal" onclick="cerrarFirma()">&times;</span>
            <h2>Confirmación de Entrega</h2>

            <div class="modal-athlete-summary" style="margin-bottom: 1.25rem;">
                <p>Número de atleta: <b id="modal-folio-txt">-</b></p>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem; text-align: left;">
                <label for="modal-talla-select" style="font-weight: 700; display: block; margin-bottom: 0.5rem; color: var(--dark-neutral);">
                    Talla de Playera Entregada:
                </label>
                <select id="modal-talla-select" class="form-select" required style="width: 100%; padding: 0.8rem; border: 2px solid var(--border-color); border-radius: 0.5rem;">
                    <option value="">-- Seleccione una talla entregada --</option>
                    <option value="XS"> Extra Chica (XS)</option>
                    <option value="CH">Chica (CH)</option>
                    <option value="M">Mediana (M)</option>
                    <option value="G">Grande (G)</option>
                    <option value="XG">Extra Grande (XG)</option>
                    <option value="2XL">Extra Grande (2XL)</option>
                </select>
            </div>

            <label style="font-weight: 700; display: block; margin-bottom: 0.5rem; text-align: left; color: var(--dark-neutral);">
                Firma Digital de Conformidad:
            </label>
            <div class="canvas-container" style="background-color: #F1F5F9; padding: 1rem; border-radius: 0.5rem; border: 2px dashed #CBD5E1;">
                <canvas id="canvas-firma" width="450" height="200" style="background-color: white; border: 1px solid #94A3B8; border-radius: 0.375rem; display: block; margin: 0 auto; touch-action: none;"></canvas>
            </div>

            <div class="modal-actions" style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary-outline btn-sm" id="btn-limpiar">Limpiar Trazo</button>
                <button type="button" class="btn btn-success" id="btn-guardar-entrega">Confirmar entrega</button>
            </div>
        </div>
    </div>

    <!-- MODAL DE DETALLES -->
    <div id="modal-detalles" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 480px; box-sizing: border-box;">
            <span class="close-modal" onclick="cerrarDetalles()">&times;</span>

            <h2 style="color: #0F172A; border-bottom: 2px solid #E2E8F0; padding-bottom: 0.5rem; margin-top: 0; margin-bottom: 0.5rem;">
                Resumen de Entrega
            </h2>

            <div class="detalles-body" style="text-align: left; margin-top: 1.25rem; line-height: 1.7; color: #334155;">
                <p><b>Folio del Atleta:</b> <span id="det-folio" style="font-weight: 700; color: #0F172A;">-</span></p>
                <p><b>Nombre Completo:</b> <span id="det-atleta">-</span></p>
                <p><b>Categoría Inscrita:</b> <span id="det-categoria">-</span></p>

                <p style="margin-bottom: 0.75rem;">
                    <b>Talla de Playera:</b>
                    <span id="det-talla" style="font-weight: 700; background-color: #F1F5F9; padding: 0.2rem 0.6rem; border-radius: 4px; border: 1px solid #CBD5E1;">-</span>

                    <button type="button" id="btn-activar-cambio" onclick="mostrarSeccionCambio()" style="margin-left: 1rem; background: none; border: none; color: #2563EB; cursor: pointer; text-decoration: underline; font-size: 0.85rem; font-weight: 600;">
                        ✏️ Cambiar Talla
                    </button>
                </p>

                <div id="seccion-cambio-talla" style="display: none; background-color: #FFFBEB; border: 1px solid #FDE68A; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1.25rem; box-sizing: border-box;">
                    <label for="det-nueva-talla" style="font-weight: 700; display: block; margin-bottom: 0.4rem; font-size: 0.9rem; color: #B45309;">
                        Selecciona la Nueva Talla:
                    </label>
                    <div style="display: flex; gap: 0.5rem;">
                        <select id="det-nueva-talla" style="padding: 0.5rem; border-radius: 6px; border: 1px solid #CBD5E1; flex-grow: 1; background-color: white; font-family: inherit;">
                            <option value="XS">Chica (XS)</option>
                            <option value="CH">Chica (CH)</option>
                            <option value="M">Mediana (M)</option>
                            <option value="G">Grande (G)</option>
                            <option value="XG">Extra Grande (XG)</option>
                            <option value="2XL">Extra Grande (2XL)</option>
                        </select>
                        <button type="button" onclick="procesarCambioTalla()" style="background-color: #D97706; color: white; border: none; padding: 0.5rem 1.2rem; border-radius: 6px; font-weight: 700; cursor: pointer; transition: background 0.2s;">
                            Guardar
                        </button>
                    </div>
                </div>

                <p><b>Fecha y Hora de Validación:</b> <span id="det-fecha">-</span></p>

                <p><b>Kit Gestionado por:</b> <span id="det-staff" style="color: #2563EB; font-weight: 700;">-</span></p>

                <label style="font-weight: 700; display: block; margin-top: 1.25rem; margin-bottom: 0.5rem; color: #0F172A;">
                    Firma Digital de Conformidad:
                </label>
                <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 0.5rem; padding: 0.75rem; text-align: center; box-sizing: border-box;">
                    <img id="det-firma-img" src="" alt="Firma Digital" style="max-width: 100%; height: auto; background-color: #ffffff; border: 1px solid #94A3B8; border-radius: 0.375rem; box-shadow: inset 0 2px 4px rgba(0,0,0,0.06); display: block; margin: 0 auto;">
                </div>
            </div>

            <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: center; width: 100%;">
                <button type="button" class="btn btn-secondary" onclick="cerrarDetalles()" style="padding: 0.6rem 2.5rem; border-radius: 0.5rem; cursor: pointer; font-weight: 600; border: 1px solid #CBD5E1; background-color: #F8FAFC; color: #334155;">
                    Cerrar Ventana
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script src="public/js/buscador.js"></script>
    <script src="public/js/firma.js"></script>
    <script src="public/js/detalles.js"></script>

    <script src="public/js/qr.js?v=1.0"></script>
</body>

</html>