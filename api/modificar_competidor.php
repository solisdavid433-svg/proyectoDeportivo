<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/modificar_competidor.php
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


$folio     = isset($_POST['comp_folio']) ? intval($_POST['comp_folio']) : 0;
$nombre    = isset($_POST['comp_nombre']) ? trim($_POST['comp_nombre']) : '';
$categoria = isset($_POST['comp_categoria']) ? trim($_POST['comp_categoria']) : '';
$evento_id = isset($_POST['evento_id']) ? intval($_POST['evento_id']) : 0;

if ($folio === 0 || $evento_id === 0 || empty($nombre) || empty($categoria)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos para actualizar al competidor.']);
    exit;
}

// Actualizamos al atleta aislando la consulta estrictamente a su evento
$sql = "UPDATE tbl_competidores 
        SET nombre = ?, categoria = ? 
        WHERE folio = ? AND evento_id = ?";

$params = array($nombre, $categoria, $folio, $evento_id);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar los datos en la base de datos.']);
    exit;
}

echo json_encode(['success' => true]);
