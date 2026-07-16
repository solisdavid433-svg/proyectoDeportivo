<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/obtener_categorias.php (CATÁLOGO DINÁMICO DE CATEGORÍAS POR EVENTO)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php';

// Validamos permisos mínimos de sesión
if (!isset($_SESSION['usuario_rol'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$evento_id = isset($_GET['evento_id']) ? intval($_GET['evento_id']) : 0;

if ($evento_id === 0) {
    echo json_encode(['success' => false, 'categorias' => []]);
    exit;
}

// Hacemos un mapeo único y ordenado de las categorías del evento actual
$sql = "SELECT DISTINCT categoria FROM tbl_competidores WHERE evento_id = ? ORDER BY categoria ASC";
$stmt = sqlsrv_query($conn, $sql, array($evento_id));

$categorias = [];
if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $categorias[] = $row['categoria'];
    }
}

echo json_encode(['success' => true, 'categorias' => $categorias]);
