<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/guardar_staff.php (CRUD CON HASH DE CONTRASEÑA SEGURO)
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

$id        = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
$nombre    = isset($_POST['staff_nombre']) ? trim($_POST['staff_nombre']) : '';
$usuario   = isset($_POST['staff_usuario']) ? trim($_POST['staff_usuario']) : '';
$password  = isset($_POST['staff_password']) ? trim($_POST['staff_password']) : '';
$evento_id = isset($_POST['staff_evento_id']) ? intval($_POST['staff_evento_id']) : null;

if (empty($nombre) || empty($usuario)) {
    echo json_encode(['success' => false, 'message' => 'El nombre y correo de acceso son obligatorios.']);
    exit;
}

$evento_param = ($evento_id === 0) ? null : $evento_id;

if ($id === 0) {
    // 🆕 OPERACIÓN: ALTA DE NUEVO STAFF
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'La contraseña es obligatoria para cuentas nuevas.']);
        exit;
    }

    // 💥 INTEGRACIÓN CRÍTICA: Convertimos la clave a un Hash BCRYPT seguro compatible con tu login.php
    $password_encriptada = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO tbl_usuarios (nombre, correo, password, rol, estatus, evento_asignado_id) VALUES (?, ?, ?, 'Staff', 1, ?)";
    $params = array($nombre, $usuario, $password_encriptada, $evento_param);
} else {
    // 📝 OPERACIÓN: EDICIÓN DE PERFIL
    if (!empty($password)) {
        // Si el admin asignó una contraseña nueva en la edición, también la encriptamos
        $password_encriptada = password_hash($password, PASSWORD_BCRYPT);

        $sql = "UPDATE tbl_usuarios SET nombre = ?, correo = ?, password = ?, evento_asignado_id = ? WHERE id = ?";
        $params = array($nombre, $usuario, $password_encriptada, $evento_param, $id);
    } else {
        // Si se dejó en blanco, modificamos los datos sin tocar la contraseña existente
        $sql = "UPDATE tbl_usuarios SET nombre = ?, correo = ?, evento_asignado_id = ? WHERE id = ?";
        $params = array($nombre, $usuario, $evento_param, $id);
    }
}

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    $errors = sqlsrv_errors();
    $msg = isset($errors[0]['message']) ? $errors[0]['message'] : 'Error interno de inserción.';
    echo json_encode(['success' => false, 'message' => $msg]);
} else {
    echo json_encode(['success' => true]);
}
