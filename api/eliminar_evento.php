<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/eliminar_evento.php (BORRADO SEGURO EN CASCADA TRANSACCIONAL)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$evento_id = isset($input['id']) ? intval($input['id']) : 0;

if ($evento_id === 0) {
    echo json_encode(['success' => false, 'message' => 'ID de competencia no válido.']);
    exit;
}

sqlsrv_begin_transaction($conn);

// Paso A: Eliminar las firmas y registros de entregas vinculados a este evento
$sqlA = "DELETE FROM tbl_entregas_kits WHERE evento_id = ?";
$stmtA = sqlsrv_query($conn, $sqlA, array($evento_id));

// Paso B: Eliminar el padrón de competidores de este evento
$sqlB = "DELETE FROM tbl_competidores WHERE evento_id = ?";
$stmtB = sqlsrv_query($conn, $sqlB, array($evento_id));

// Paso C: Eliminar el evento raíz
$sqlC = "DELETE FROM tbl_eventos WHERE id = ?";
$stmtC = sqlsrv_query($conn, $sqlC, array($evento_id));

if ($stmtA !== false && $stmtB !== false && $stmtC !== false) {
    sqlsrv_commit($conn);
    echo json_encode(['success' => true]);
} else {
    sqlsrv_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Error al ejecutar la depuración relacional en SQL Server.']);
}
