<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/cambiar_categoria.php (AUDITORÍA Y CAMBIO SEGMENTADO)
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

// PREVENCIÓN CRÍTICA: Capturamos el ID del evento enviado por el JSON payload
$evento_id = isset($input['evento_id']) ? intval($input['evento_id']) : 0;
$encargado_id = $_SESSION['usuario_id']; // ID del supervisor logueado

if ($folio === 0 || empty($nueva_cat) || $evento_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos o contexto de competencia no detectado.']);
    exit;
}

// 1. OBTENER LA CATEGORÍA ACTUAL SEGMENTADA POR EVENTO
// Agregamos "AND evento_id = ?" para no leer datos de otra competencia
$sqlCheck = "SELECT categoria, categoria_original FROM tbl_competidores WHERE folio = ? AND evento_id = ?";
$stmtCheck = sqlsrv_query($conn, $sqlCheck, array($folio, $evento_id));
$atleta = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

if (!$atleta) {
    echo json_encode(['success' => false, 'message' => 'El competidor no existe en esta competencia.']);
    exit;
}

// Si es la primera vez que se cambia, respaldamos la categoría actual como la original
$categoriaOriginal = $atleta['categoria_original'] ? $atleta['categoria_original'] : $atleta['categoria'];

// 2. EJECUTAR EL UPDATE CON LA AUDITORÍA COMPLETA Y SEGMENTACIÓN POR EVENTO
// Agregamos "AND evento_id = ?" para aislar el cambio únicamente a la carrera activa
$sqlExecute = "UPDATE tbl_competidores 
               SET categoria = ?, 
                   categoria_original = ?, 
                   encargado_id_cambio = ?, 
                   fecha_cambio_cat = GETDATE() 
               WHERE folio = ? AND evento_id = ?";

$params = array($nueva_cat, $categoriaOriginal, $encargado_id, $folio, $evento_id);
$stmtExecute = sqlsrv_query($conn, $sqlExecute, $params);

if ($stmtExecute === false) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la categoría en SQL Server.']);
    exit;
}

echo json_encode(['success' => true]);
