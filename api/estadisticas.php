<?php
session_start();

/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['usuario_rol']) || ($_SESSION['usuario_rol'] !== 'Administrador' && $_SESSION['usuario_rol'] !== 'Encargado')) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

// Capturamos el evento_id que nos manda el archivo de JS
$evento_id = isset($_GET['evento_id']) ? intval($_GET['evento_id']) : 1;

// --------------------------------------------------------------------------
// METRICA 1: AVANCE GENERAL FILTRADO POR EVENTO
// --------------------------------------------------------------------------
// Nota: tbl_competidores debería estar vinculada a un evento en un escenario real,
// si tu tabla global de atletas de prueba no tiene evento_id por ahora, cuenta el total general:
$qTotal = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM tbl_competidores");
$rTotal = sqlsrv_fetch_array($qTotal, SQLSRV_FETCH_ASSOC);
$totalAtletas = $rTotal['total'] ?? 0;

// Kits entregados específicos de este evento
$qEntregados = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM tbl_entregas_kits WHERE evento_id = ?", array($evento_id));
$rEntregados = sqlsrv_fetch_array($qEntregados, SQLSRV_FETCH_ASSOC);
$totalEntregados = $rEntregados['total'] ?? 0;

// Cambios específicos de este evento
$qCambios = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM tbl_entregas_kits WHERE hubo_cambio = 1 AND evento_id = ?", array($evento_id));
$rCambios = sqlsrv_fetch_array($qCambios, SQLSRV_FETCH_ASSOC);
$totalCambios = $rCambios['total'] ?? 0;

$totalPendientes = $totalAtletas - $totalEntregados;
$porcentajeAvance = $totalAtletas > 0 ? round(($totalEntregados / $totalAtletas) * 100, 1) : 0;

// --------------------------------------------------------------------------
// METRICA 2: INVENTARIO DE PLAYERAS FILTRADO POR EVENTO
// --------------------------------------------------------------------------
$sqlTallas = "SELECT talla_playera, COUNT(*) as cantidad FROM tbl_entregas_kits WHERE evento_id = ? GROUP BY talla_playera";
$qTallas = sqlsrv_query($conn, $sqlTallas, array($evento_id));

$tallas = ['CH' => 0, 'M' => 0, 'G' => 0, 'XG' => 0];
while ($row = sqlsrv_fetch_array($qTallas, SQLSRV_FETCH_ASSOC)) {
    $tallas[$row['talla_playera']] = $row['cantidad'];
}

// --------------------------------------------------------------------------
// METRICA 3: PRODUCTIVIDAD DEL STAFF EN ESTE EVENTO
// --------------------------------------------------------------------------
$sqlStaff = "SELECT u.nombre as operador, COUNT(e.competidor_id) as total_entregas
             FROM tbl_usuarios u
             INNER JOIN tbl_entregas_kits e ON u.id = e.staff_id
             WHERE e.evento_id = ?
             GROUP BY u.nombre
             ORDER BY total_entregas DESC";
$qStaff = sqlsrv_query($conn, $sqlStaff, array($evento_id));

$rankingStaff = [];
while ($row = sqlsrv_fetch_array($qStaff, SQLSRV_FETCH_ASSOC)) {
    $rankingStaff[] = $row;
}

echo json_encode([
    'success' => true,
    'resumen' => [
        'total' => $totalAtletas,
        'entregados' => $totalEntregados,
        'pendientes' => $totalPendientes,
        'cambios' => $totalCambios,
        'porcentaje' => $porcentajeAvance
    ],
    'tallas' => $tallas,
    'staff' => $rankingStaff
]);
