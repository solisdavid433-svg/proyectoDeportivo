<?php
session_start();

/** @var resource $conn */


header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Staff') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$folio = isset($input['folio']) ? intval($input['folio']) : 0;
$firma = isset($input['firma']) ? $input['firma'] : '';
$talla = isset($input['talla']) ? trim($input['talla']) : ''; // <-- Capturamos la talla
$evento_id = isset($input['evento_id']) ? intval($input['evento_id']) : 1;
$staff_id = $_SESSION['usuario_id'];

if ($folio === 0 || empty($firma) || empty($talla)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos (Verifique la talla).']);
    exit;
}

// 1. Insertamos incluyendo la columna talla_playera
$sqlInsert = "INSERT INTO tbl_entregas_kits (evento_id, competidor_id, staff_id, firma_base64, hubo_cambio, talla_playera) 
              VALUES (?, ?, ?, ?, 0, ?)";
$paramsInsert = array($evento_id, $folio, $staff_id, $firma, $talla); // <-- Pasamos el parámetro
$stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);

if ($stmtInsert === false) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la firma digital en la base de datos.']);
    exit;
}

// 2. Cambiamos el estatus del competidor
$sqlUpdate = "UPDATE tbl_competidores SET estatus_entrega = 'ENTREGADO' WHERE folio = ?";
$paramsUpdate = array($folio);
$stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);

if ($stmtUpdate === false) {
    echo json_encode(['success' => false, 'message' => 'Firma guardada, pero falló el cambio de estatus.']);
    exit;
}

echo json_encode(['success' => true]);
