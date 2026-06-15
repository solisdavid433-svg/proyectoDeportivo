<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/buscar.php (BUSCADOR EN TIEMPO REAL CON FILTRO DINÁMICO)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */


header('Content-Type: application/json');
require_once '../config/db.php';

// Si no hay sesión de Staff o Admin, bloqueamos la API por seguridad
if (!isset($_SESSION['usuario_rol'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Capturamos el término de búsqueda que viene por la URL (ej: ?termino=carlos)
$termino = isset($_GET['termino']) ? trim($_GET['termino']) : '';

if (empty($termino)) {
    echo json_encode([]); // Si está vacío, regresamos un arreglo vacío
    exit;
}

// Consulta con LEFT JOIN para saber en tiempo real si el kit sufrió un cambio posterior
$sql = "SELECT c.folio, c.nombre AS nombre, c.categoria, c.estatus_entrega, e.hubo_cambio
        FROM tbl_competidores c
        LEFT JOIN tbl_entregas_kits e ON c.folio = e.competidor_id
        WHERE (c.folio = ? OR c.nombre COLLATE Modern_Spanish_CI_AI LIKE ?)
        ORDER BY c.nombre ASC";

// Agregamos los comodines '%' para que busque en cualquier parte del nombre
$buscarNombre = "%" . $termino . "%";

// El primer parámetro intenta evaluar si es un número (folio), si no, mandamos un valor neutro
$buscarFolio = is_numeric($termino) ? intval($termino) : -1;

$params = array($buscarFolio, $buscarNombre);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['error' => 'Fallo en la consulta de base de datos.']);
    exit;
}

$resultados = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $resultados[] = $row;
}

// Devolvemos los atletas encontrados en formato JSON
echo json_encode($resultados);
