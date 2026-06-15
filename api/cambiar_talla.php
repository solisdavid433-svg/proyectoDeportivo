<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/cambiar_talla.php (PROCESADOR DE CAMBIOS E HISTORIAL)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php';

// Seguridad: Tanto el Staff como el Encargado pueden hacer cambios
if (!isset($_SESSION['usuario_rol']) || ($_SESSION['usuario_rol'] !== 'Staff' && $_SESSION['usuario_rol'] !== 'Encargado')) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$folio = isset($input['folio']) ? intval($input['folio']) : 0;
$talla = isset($input['talla']) ? trim($input['talla']) : '';
$staff_id = $_SESSION['usuario_id']; // Guardamos quién resolvió el cambio operativo

if ($folio === 0 || empty($talla)) {
    echo json_encode(['success' => false, 'message' => 'Datos insuficientes para el cambio.']);
    exit;
}

// UPDATE CRÍTICO: Modifica la talla, activa el flag de cambio y guarda la fecha y el responsable actual
$sql = "UPDATE tbl_entregas_kits 
        SET talla_playera = ?, 
            hubo_cambio = 1, 
            fecha_modificacion = GETDATE(), 
            staff_id = ? 
        WHERE competidor_id = ?";

$params = array($talla, $staff_id, $folio);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el registro en SQL Server.']);
    exit;
}

echo json_encode(['success' => true]);
