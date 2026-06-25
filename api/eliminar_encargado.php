<?php
session_start();

/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$enc_id = isset($input['id']) ? intval($input['id']) : 0;

if ($enc_id === 0) {
    echo json_encode(['success' => false, 'message' => 'ID no válido.']);
    exit;
}

// Candado estricto: Solo elimina si el rol es Encargado para evitar accidentes
$sql = "DELETE FROM tbl_usuarios WHERE id = ? AND rol = 'Encargado'";
$stmt = sqlsrv_query($conn, $sql, array($enc_id));

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'No se puede eliminar el supervisor seleccionado.']);
} else {
    echo json_encode(['success' => true]);
}
