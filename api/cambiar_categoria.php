<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/cambiar_categoria.php (AUDITORÍA Y CAMBIO DE CATEGORÍA)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php';

// Candado estricto: Solo el Encargado (o el Administrador) puede ejecutar esto
if (!isset($_SESSION['usuario_rol']) || ($_SESSION['usuario_rol'] !== 'Encargado' && $_SESSION['usuario_rol'] !== 'Administrador')) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Privilegios insuficientes.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$folio = isset($input['folio']) ? intval($input['folio']) : 0;
$nueva_cat = isset($input['nueva_categoria']) ? trim($input['nueva_categoria']) : '';
$encargado_id = $_SESSION['usuario_id']; // ID del supervisor logueado

if ($folio === 0 || empty($nueva_cat)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos para procesar el cambio.']);
    exit;
}

// 1. OBTENER LA CATEGORÍA ACTUAL PARA RESPALDARLA EN CASO DE SER SU PRIMER CAMBIO
$sqlCheck = "SELECT categoria, categoria_original FROM tbl_competidores WHERE folio = ?";
$stmtCheck = sqlsrv_query($conn, $sqlCheck, array($folio));
$atleta = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

if (!$atleta) {
    echo json_encode(['success' => false, 'message' => 'El competidor no existe.']);
    exit;
}

// Si es la primera vez que se cambia, respaldamos la categoría actual como la original
$categoriaOriginal = $atleta['categoria_original'] ? $atleta['categoria_original'] : $atleta['categoria'];

// 2. EJECUTAR EL UPDATE CON LA AUDITORÍA COMPLETA
$sqlExecute = "UPDATE tbl_competidores 
               SET categoria = ?, 
                   categoria_original = ?, 
                   encargado_id_cambio = ?, 
                   fecha_cambio_cat = GETDATE() 
               WHERE folio = ?";

$params = array($nueva_cat, $categoriaOriginal, $encargado_id, $folio);
$stmtExecute = sqlsrv_query($conn, $sqlExecute, $params);

if ($stmtExecute === false) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la categoría en SQL Server.']);
    exit;
}

echo json_encode(['success' => true]);
