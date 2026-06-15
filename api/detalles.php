<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/detalles.php (EXTRACCIÓN DE AUDITORÍA CON INNER JOIN)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php';

// Validamos seguridad básica de la sesión
if (!isset($_SESSION['usuario_rol'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Capturamos el folio que nos manda el archivo detalles.js
$folio = isset($_GET['folio']) ? intval($_GET['folio']) : 0;

if ($folio === 0) {
    echo json_encode(['success' => false, 'message' => 'Folio inválido.']);
    exit;
}

// Consulta con triple unión para jalar datos operacionales, la firma y el nombre del Staff que atendió
$sql = "SELECT c.folio, c.nombre AS atleta, c.categoria, 
               e.fecha_entrega, e.talla_playera, e.firma_base64, 
               u.nombre AS nombre_staff
        FROM tbl_competidores c
        INNER JOIN tbl_entregas_kits e ON c.folio = e.competidor_id
        INNER JOIN tbl_usuarios u ON e.staff_id = u.id
        WHERE c.folio = ?";

$params = array($folio);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Error al consultar la base de datos.']);
    exit;
}

$data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No se encontraron registros de entrega para este atleta.']);
    exit;
}

// Convertimos el objeto de fecha nativo de SQL Server a un formato de texto legible
if ($data['fecha_entrega'] instanceof DateTime) {
    $data['fecha_entrega'] = $data['fecha_entrega']->format('d/m/Y g:i A');
}

// Le regresamos los datos limpios en formato JSON a detalles.js
echo json_encode(['success' => true, 'data' => $data]);
