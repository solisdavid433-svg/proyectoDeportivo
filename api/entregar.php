<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/guardar_entrega.php (GUARDADO DE FIRMA RELACIONAL Y PREVENTIVO)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php';

// Candado estricto de seguridad: Solo el rol Staff puede registrar firmas de entrega
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Staff') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$folio = isset($input['folio']) ? intval($input['folio']) : 0;
$firma = isset($input['firma']) ? $input['firma'] : '';
$talla = isset($input['talla']) ? trim($input['talla']) : '';

// 💥 TRUCO MAESTRO: Tomamos de forma obligatoria el ID del evento que el Staff confirmó en su sesión.
// Si por alguna razón extrema no existiera, recurrimos al del input como un respaldo secundario.
$evento_id = isset($_SESSION['evento_id_staff']) ? intval($_SESSION['evento_id_staff']) : (isset($input['evento_id']) ? intval($input['evento_id']) : 0);
$staff_id  = $_SESSION['usuario_id'];

if ($folio === 0 || empty($firma) || empty($talla) || $evento_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos o contexto de competencia no detectado.']);
    exit;
}

// ==========================================================================
// 🚨 EL ESCUDO: VALIDACIÓN ANTIDUPLICADOS REAL-TIME
// ==========================================================================
// Consultamos si este folio ya tiene un registro de kit guardado en esta carrera
$sqlCheck = "SELECT COUNT(*) AS total FROM tbl_entregas_kits WHERE competidor_id = ? AND evento_id = ?";
$stmtCheck = sqlsrv_query($conn, $sqlCheck, array($folio, $evento_id));

if ($stmtCheck !== false) {
    $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
    if (intval($rowCheck['total']) > 0) {
        // Si el conteo es mayor a 0, congelamos la inserción y mandamos la alerta
        echo json_encode([
            'success' => false,
            'message' => '⚠️ OPERACIÓN RECHAZADA: Este competidor ya recogió su kit anteriormente.'
        ]);
        exit;
    }
}

// PROCESO ATÓMICO DE GUARDADO (SI PASÓ EL FILTRO PREVIO)
// Iniciamos una transacción atómica para garantizar la integridad relacional de ambas tablas
sqlsrv_begin_transaction($conn);

// 1. Insertamos incluyendo el evento_id relacional correcto
$sqlInsert = "INSERT INTO tbl_entregas_kits (evento_id, competidor_id, staff_id, firma_base64, hubo_cambio, talla_playera) 
              VALUES (?, ?, ?, ?, 0, ?)";
$paramsInsert = array($evento_id, $folio, $staff_id, $firma, $talla);
$stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);

if ($stmtInsert === false) {
    sqlsrv_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Error al guardar la firma digital en la base de datos.']);
    exit;
}

// 2. Cambiamos el estatus del competidor
// CORRECCIÓN CRÍTICA: Agregamos "AND evento_id = ?" para evitar actualizar folios repetidos de otras carreras
$sqlUpdate = "UPDATE tbl_competidores SET estatus_entrega = 'ENTREGADO' WHERE folio = ? AND evento_id = ?";
$paramsUpdate = array($folio, $evento_id);
$stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);

if ($stmtUpdate === false) {
    sqlsrv_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Firma guardada, pero falló el cambio de estatus segmentado.']);
    exit;
}

// ÉXITO TOTAL: Confirmamos la transacción en el motor SQL Server 2014
sqlsrv_commit($conn);
echo json_encode(['success' => true]);
