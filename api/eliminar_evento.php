<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/eliminar_evento.php (BORRADO EN CASCADA COMPLETO CON DESASIGNACIÓN)
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

// Receptor de ID
$evento_id = 0;
if (isset($_POST['id'])) {
    $evento_id = intval($_POST['id']);
}
if ($evento_id === 0 && isset($_POST['evento_id'])) {
    $evento_id = intval($_POST['evento_id']);
}
if ($evento_id === 0 && isset($_POST['id_evento'])) {
    $evento_id = intval($_POST['id_evento']);
}

if ($evento_id === 0) {
    $json_raw = file_get_contents('php://input');
    if (!empty($json_raw)) {
        $data_json = json_decode($json_raw, true);
        if (isset($data_json['id'])) {
            $evento_id = intval($data_json['id']);
        }
        if ($evento_id === 0 && isset($data_json['evento_id'])) {
            $evento_id = intval($data_json['evento_id']);
        }
    }
}

if ($evento_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Error: ID de competencia no recibido por el servidor.']);
    exit;
}

// 💥 COBERTURA TOTAL EN CASCADA (4 PASOS)
sqlsrv_begin_transaction($conn);

// Paso 1: Eliminar las entregas de kits vinculadas a esta carrera
$sql_kits = "DELETE FROM tbl_entregas_kits WHERE evento_id = ?";
$stmt_kits = sqlsrv_query($conn, $sql_kits, array($evento_id));

if ($stmt_kits === false) {
    $errors = sqlsrv_errors();
    sqlsrv_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Fallo en Paso 1 (Kits): ' . $errors[0]['message']], JSON_UNESCAPED_UNICODE);
    exit;
}

// Paso 2: Eliminar los competidores registrados en esta carrera
$sql_comp = "DELETE FROM tbl_competidores WHERE evento_id = ?";
$stmt_comp = sqlsrv_query($conn, $sql_comp, array($evento_id));

if ($stmt_comp === false) {
    $errors = sqlsrv_errors();
    sqlsrv_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Fallo en Paso 2 (Competidores): ' . $errors[0]['message']], JSON_UNESCAPED_UNICODE);
    exit;
}

// 🎯 PASO 3 (NUEVO): Desasignar a los usuarios del Staff vinculados a este evento
// Ponemos en NULL la columna para romper la FK sin borrar sus cuentas
$sql_user = "UPDATE tbl_usuarios SET evento_asignado_id = NULL WHERE evento_asignado_id = ?";
$stmt_user = sqlsrv_query($conn, $sql_user, array($evento_id));

if ($stmt_user === false) {
    $errors = sqlsrv_errors();
    sqlsrv_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Fallo en Paso 3 (Usuarios): ' . $errors[0]['message']], JSON_UNESCAPED_UNICODE);
    exit;
}

// Paso 4: Eliminar finalmente el registro maestro en tbl_eventos
$sql_even = "DELETE FROM tbl_eventos WHERE id = ?";
$stmt_even = sqlsrv_query($conn, $sql_even, array($evento_id));

if ($stmt_even === false) {
    $errors = sqlsrv_errors();
    sqlsrv_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Fallo en Paso 4 (Eventos): ' . $errors[0]['message']], JSON_UNESCAPED_UNICODE);
    exit;
}

// Si todo se ejecutó de forma impecable, guardamos cambios de golpe
sqlsrv_commit($conn);
echo json_encode(['success' => true, 'message' => 'La competencia y todos sus vínculos se eliminaron correctamente de la base de datos.']);
