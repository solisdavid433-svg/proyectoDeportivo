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
$staff_id = isset($input['id']) ? intval($input['id']) : 0;

if ($staff_id === 0) {
    echo json_encode(['success' => false, 'message' => 'ID no válido.']);
    exit;
}

$sql = "DELETE FROM tbl_usuarios WHERE id = ? AND rol = 'Staff'";
$stmt = sqlsrv_query($conn, $sql, array($staff_id));

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'No se puede eliminar el operador (tiene registros asociados).']);
} else {
    echo json_encode(['success' => true]);
}
