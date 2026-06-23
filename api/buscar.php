<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/buscar.php (BUSCADOR EN TIEMPO REAL CON FILTRO DINÁMICO CORREGIDO)
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

// 1. Intentamos obtener el evento_id que viene directamente por la URL (Caso Encargado)
$evento_id = isset($_GET['evento_id']) ? intval($_GET['evento_id']) : 0;

// 2. Si no viene por URL (Caso Staff), aplicamos la lógica inteligente y el control de expulsión
if ($evento_id === 0) {
    if (isset($_SESSION['evento_id_staff'])) {
        // 🚨 CONTROL DE EXPULSIÓN REAL-TIME:
        // Verificamos que su sesión coincida con el evento activo en la BD
        $sql_check = "SELECT id FROM tbl_eventos WHERE id = ? AND es_activo = 1";
        $stmt_check = sqlsrv_query($conn, $sql_check, array($_SESSION['evento_id_staff']));

        if ($stmt_check === false || !sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC)) {
            unset($_SESSION['evento_id_staff']); // Rompemos la sesión de mesa
            echo json_encode(['error' => 'evento_cambiado']); // Avisamos al JavaScript
            exit;
        }
        // Si sigue activo, lo asignamos de forma definitiva
        $evento_id = intval($_SESSION['evento_id_staff']);
    } else {
        // Fallback en vivo: Si el staff no ha confirmado pero busca, nos alineamos al evento activo en la BD
        $sql_active = "SELECT id FROM tbl_eventos WHERE es_activo = 1";
        $stmt_active = sqlsrv_query($conn, $sql_active);
        if ($stmt_active !== false && $row_a = sqlsrv_fetch_array($stmt_active, SQLSRV_FETCH_ASSOC)) {
            $evento_id = intval($row_a['id']);
        }
    }
}

// 3. Respaldo secundario si sigue siendo 0 (Caso de reingreso de Encargado sin parámetro de URL)
if ($evento_id === 0 && isset($_SESSION['evento_id_activo'])) {
    $evento_id = intval($_SESSION['evento_id_activo']);
}

// 4. Validación estricta de salida segura
if (empty($termino) || $evento_id === 0) {
    echo json_encode([]);
    exit;
}

// Consulta con LEFT JOIN para saber en tiempo real si el kit sufrió un cambio posterior
$sql = "SELECT c.folio, c.nombre AS nombre, c.categoria, c.estatus_entrega, e.hubo_cambio
        FROM tbl_competidores c
        LEFT JOIN tbl_entregas_kits e ON c.folio = e.competidor_id
        WHERE (c.folio = ? OR c.nombre COLLATE Modern_Spanish_CI_AI LIKE ?) AND c.evento_id = ?
        ORDER BY c.nombre ASC";

// Agregamos los comodines '%' para que busque en cualquier parte del nombre
$buscarNombre = "%" . $termino . "%";

// El primer parámetro intenta evaluar si es un número (folio), si no, mandamos un valor neutro
$buscarFolio = is_numeric($termino) ? intval($termino) : -1;

$params = array($buscarFolio, $buscarNombre, $evento_id);
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
