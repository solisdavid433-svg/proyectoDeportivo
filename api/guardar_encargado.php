<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/guardar_encargado.php (CRUD DE SUPERVISORES DE CONTROL)
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

$id       = isset($_POST['enc_id']) ? intval($_POST['enc_id']) : 0;
$nombre   = isset($_POST['enc_nombre']) ? trim($_POST['enc_nombre']) : '';
$correo   = isset($_POST['enc_correo']) ? trim($_POST['enc_correo']) : '';
$password = isset($_POST['enc_password']) ? trim($_POST['enc_password']) : '';

if (empty($nombre) || empty($correo)) {
    echo json_encode(['success' => false, 'message' => 'El nombre y correo de acceso son obligatorios.']);
    exit;
}

if ($id === 0) {
    // 🆕 OPERACIÓN: REGISTRO DE NUEVO ENCARGADO
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'La contraseña es obligatoria para cuentas nuevas.']);
        exit;
    }

    $password_encriptada = password_hash($password, PASSWORD_BCRYPT);

    // Inserción limpia mapeada con estatus tipo BIT (1)
    $sql = "INSERT INTO tbl_usuarios (nombre, correo, password, rol, estatus) VALUES (?, ?, ?, 'Encargado', 1)";
    $params = array($nombre, $correo, $password_encriptada);
} else {
    // 📝 OPERACIÓN: EDICIÓN DE PERFIL EXISTENTE
    if (!empty($password)) {
        $password_encriptada = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE tbl_usuarios SET nombre = ?, correo = ?, password = ? WHERE id = ? AND rol = 'Encargado'";
        $params = array($nombre, $correo, $password_encriptada, $id);
    } else {
        $sql = "UPDATE tbl_usuarios SET nombre = ?, correo = ? WHERE id = ? AND rol = 'Encargado'";
        $params = array($nombre, $correo, $id);
    }
}

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    $errors = sqlsrv_errors();
    $msg = isset($errors[0]['message']) ? $errors[0]['message'] : 'Error interno de inserción en SQL Server.';
    echo json_encode(['success' => false, 'message' => $msg]);
} else {
    echo json_encode(['success' => true]);
}
