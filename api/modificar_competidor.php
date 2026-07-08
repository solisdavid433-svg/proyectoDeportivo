<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/modificar_competidor.php (API DE ACTUALIZACIÓN DE CORREDORES)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php';

// Candado estricto de seguridad: Solo el Admin puede alterar el padrón de atletas
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. No autorizado.']);
    exit;
}

// Capturamos las variables enviadas desde el Form Data
$folio     = isset($_POST['comp_folio']) ? intval($_POST['comp_folio']) : 0;
$nombre    = isset($_POST['comp_nombre']) ? trim($_POST['comp_nombre']) : '';
$categoria = isset($_POST['comp_categoria']) ? trim($_POST['comp_categoria']) : '';

if ($folio === 0 || empty($nombre) || empty($categoria)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios para la actualización.']);
    exit;
}

// Iniciamos una transacción atómica en SQL Server
sqlsrv_begin_transaction($conn);

// 1. Actualizamos los datos principales en la tabla de competidores
$sqlAtleta = "UPDATE tbl_competidores SET nombre = ?, categoria = ? WHERE folio = ?";
$stmtAtleta = sqlsrv_query($conn, $sqlAtleta, array($nombre, $categoria, $folio));

// 2. Control de Incidencias: Si el kit ya fue entregado, encendemos la bandera "hubo_cambio"
$sqlIncidencia = "UPDATE tbl_entregas_kits SET hubo_cambio = 1 WHERE competidor_id = ?";
$stmtIncidencia = sqlsrv_query($conn, $sqlIncidencia, array($folio));

// Evaluamos que la actualización principal haya sido exitosa (la segunda es opcional si aún no se entrega el kit)
if ($stmtAtleta !== false) {
    sqlsrv_commit($conn);
    echo json_encode(['success' => true]);
} else {
    sqlsrv_rollback($conn);
    $errors = sqlsrv_errors();
    $msg = isset($errors[0]['message']) ? $errors[0]['message'] : 'Error interno al actualizar en SQL Server.';
    echo json_encode(['success' => false, 'message' => $msg]);
}
