<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/activar_evento.php (CONMUTADOR DE EVENTO ACTIVO GLOBAL)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Encargado') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$evento_id = isset($input['evento_id']) ? intval($input['evento_id']) : 0;

if ($evento_id === 0) {
    echo json_encode(['success' => false, 'message' => 'ID de evento no válido.']);
    exit;
}

// Iniciamos transacción para apagar el evento viejo y encender el nuevo de golpe
sqlsrv_begin_transaction($conn);

// 1. Desactivamos todos los eventos del sistema
$sqlApagar = "UPDATE tbl_eventos SET es_activo = 0";
$stmtApagar = sqlsrv_query($conn, $sqlApagar);

// 2. Activamos únicamente el evento que seleccionó el encargado
$sqlEncender = "UPDATE tbl_eventos SET es_activo = 1 WHERE id = ?";
$stmtEncender = sqlsrv_query($conn, $sqlEncender, array($evento_id));

if ($stmtApagar !== false && $stmtEncender !== false) {
    sqlsrv_commit($conn);
    // Actualizamos también su sesión local por consistencia
    $_SESSION['evento_id_activo'] = $evento_id;
    echo json_encode(['success' => true]);
} else {
    sqlsrv_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Error al conmutar el evento en SQL Server.']);
}
