<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/login.php (PROCESADOR ASÍNCRONO DE AUTENTICACIÓN)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

// Inicializamos el sistema de sesiones de PHP para recordar quién se logueó
session_start();


/** @var resource $conn */


// Cabecera para indicarle al navegador que responderemos con formato JSON
header('Content-Type: application/json');

// Incluimos el puente de conexión (subiendo un nivel de carpeta)
require_once '../config/db.php';

// Capturamos los datos enviados desde JavaScript en formato JSON
$inputData = json_decode(file_get_contents('php://input'), true);

$correo = isset($inputData['correo']) ? trim($inputData['correo']) : '';
$password = isset($inputData['password']) ? trim($inputData['password']) : '';

// Validación rápida de campos vacíos
if (empty($correo) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, llene todos los campos.']);
    exit;
}

// 1. Buscamos al usuario por su correo electrónico
$sql = "SELECT id, nombre, password, rol, estatus FROM tbl_usuarios WHERE correo = ?";
$params = array($correo);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Error al consultar la base de datos.']);
    exit;
}

// 2. Evaluamos si el registro existe
$usuario = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$usuario) {
    echo json_encode(['success' => false, 'message' => 'El correo electrónico no está registrado.']);
    exit;
}

// 3. Verificamos si el usuario fue dado de baja (Estatus 0)
if ($usuario['estatus'] == 0) {
    echo json_encode(['success' => false, 'message' => 'Su usuario se encuentra inactivo. Contacte al administrador.']);
    exit;
}

// 4. VALIDACIÓN CRÍTICA: Comparamos la contraseña escrita con el Hash de la BD
if (password_verify($password, $usuario['password'])) {

    // ¡Contraseña correcta! Guardamos los datos clave en la Sesión del Servidor
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_rol'] = $usuario['rol'];

    // Determinamos a qué pantalla redirigir según el Rol corporativo
    $redireccion = 'staff.php'; // Por defecto
    if ($usuario['rol'] === 'Administrador') {
        $redireccion = 'admin.php';
    } elseif ($usuario['rol'] === 'Encargado') {
        $redireccion = 'encargado.php';
    }

    // Respondemos con éxito y la ruta de destino
    echo json_encode([
        'success' => true,
        'redirect' => $redireccion
    ]);
} else {
    // La contraseña no coincide con el hash
    echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta. Intente de nuevo.']);
}
