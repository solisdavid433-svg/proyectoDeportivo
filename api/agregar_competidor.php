<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/agregar_competidor.php (ALTA MANUAL EXTEMPORÁNEA)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php';

// Candado estricto: Solo el Administrador puede registrar atletas de forma directa
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Privilegios insuficientes.']);
    exit;
}

$evento_id = isset($_POST['evento_id']) ? intval($_POST['evento_id']) : 0;
$folio     = isset($_POST['folio']) ? intval($_POST['folio']) : 0;
$nombre    = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$categoria = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';

if ($evento_id === 0 || $folio === 0 || empty($nombre) || empty($categoria)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
    exit;
}

// 1. VALIDAMOS QUE EL FOLIO NO EXISTA YA EN ESTE MISMO EVENTO
$sqlCheck = "SELECT COUNT(*) AS total FROM tbl_competidores WHERE folio = ? AND evento_id = ?";
$stmtCheck = sqlsrv_query($conn, $sqlCheck, array($folio, $evento_id));

if ($stmtCheck !== false) {
    $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
    if (intval($rowCheck['total']) > 0) {
        echo json_encode([
            'success' => false,
            'message' => "El folio #{$folio} ya está registrado en esta competencia."
        ]);
        exit;
    }
}

// 2. INSERTAMOS EL NUEVO ATLETA CON ESTATUS PENDIENTE
$sqlInsert = "INSERT INTO tbl_competidores (evento_id, folio, nombre, categoria, estatus_entrega) 
              VALUES (?, ?, ?, ?, 'PENDIENTE')";
$paramsInsert = array($evento_id, $folio, $nombre, $categoria);
$stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);

if ($stmtInsert === false) {
    echo json_encode(['success' => false, 'message' => 'Error al registrar al atleta en SQL Server.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Competidor dado de alta con éxito.']);
