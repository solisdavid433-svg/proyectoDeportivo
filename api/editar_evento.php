<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/editar_evento.php (ACTUALIZACIÓN DE METADATOS E INVENTARIO)
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

$id         = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
$nombre     = isset($_POST['edit_nombre']) ? trim($_POST['edit_nombre']) : '';
$fecha      = isset($_POST['edit_fecha']) ? trim($_POST['edit_fecha']) : '';
$disciplina = isset($_POST['edit_disciplina']) ? trim($_POST['edit_disciplina']) : '';

// Captura de las 6 tallas de playeras configuradas
$s_xs  = isset($_POST['edit_stock_xs']) ? intval($_POST['edit_stock_xs']) : 0;
$s_ch  = isset($_POST['edit_stock_ch']) ? intval($_POST['edit_stock_ch']) : 0;
$s_m   = isset($_POST['edit_stock_m']) ? intval($_POST['edit_stock_m']) : 0;
$s_g   = isset($_POST['edit_stock_g']) ? intval($_POST['edit_stock_g']) : 0;
$s_xg  = isset($_POST['edit_stock_xg']) ? intval($_POST['edit_stock_xg']) : 0;
$s_2xl = isset($_POST['edit_stock_2xl']) ? intval($_POST['edit_stock_2xl']) : 0;

if ($id === 0 || empty($nombre) || empty($fecha) || empty($disciplina)) {
    echo json_encode(['success' => false, 'message' => 'Campos obligatorios incompletos.']);
    exit;
}

// Ejecutamos la actualización unificada en SQL Server
$sql = "UPDATE tbl_eventos 
        SET nombre_evento = ?, fecha = ?, disciplina = ?, 
            stock_xs = ?, stock_ch = ?, stock_m = ?, stock_g = ?, stock_xg = ?, stock_2xl = ? 
        WHERE id = ?";

$params = array($nombre, $fecha, $disciplina, $s_xs, $s_ch, $s_m, $s_g, $s_xg, $s_2xl, $id);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'No se pudieron guardar las modificaciones en SQL Server.']);
} else {
    echo json_encode(['success' => true]);
}
