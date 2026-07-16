<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/estadisticas.php (MÓDULO DE ANALÍTICAS CORREGIDO CON FILTRADO ESTRICTO)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php';

// Candado de seguridad: Solo Administrador o Encargado pueden consumir estadísticas
if (!isset($_SESSION['usuario_rol']) || ($_SESSION['usuario_rol'] !== 'Administrador' && $_SESSION['usuario_rol'] !== 'Encargado')) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

// Capturamos el evento_id que nos manda el archivo de JS
$evento_id = isset($_GET['evento_id']) ? intval($_GET['evento_id']) : 1;

// --------------------------------------------------------------------------
// METRICA 1: AVANCE GENERAL FILTRADO ESTRICTAMENTE POR EVENTO
// --------------------------------------------------------------------------
//  CORRECCIÓN AQUÍ: Ahora filtramos por evento_id para que no cuente competidores de otras carreras
$sqlTotal = "SELECT COUNT(*) as total FROM tbl_competidores WHERE evento_id = ?";
$qTotal = sqlsrv_query($conn, $sqlTotal, array($evento_id));
$rTotal = sqlsrv_fetch_array($qTotal, SQLSRV_FETCH_ASSOC);
$totalAtletas = $rTotal['total'] ?? 0;

// Kits entregados específicos de este evento
$qEntregados = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM tbl_competidores WHERE estatus_entrega = 'ENTREGADO' AND evento_id = ?", array($evento_id));
$rEntregados = sqlsrv_fetch_array($qEntregados, SQLSRV_FETCH_ASSOC);
$totalEntregados = $rEntregados['total'] ?? 0;

// Cambios específicos de este evento
$qCambios = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM tbl_entregas_kits WHERE hubo_cambio = 1 AND evento_id = ?", array($evento_id));
$rCambios = sqlsrv_fetch_array($qCambios, SQLSRV_FETCH_ASSOC);
$totalCambios = $rCambios['total'] ?? 0;

// Los pendientes se calculan automáticamente sobre el total real filtrado
$totalPendientes = $totalAtletas - $totalEntregados;
$porcentajeAvance = $totalAtletas > 0 ? round(($totalEntregados / $totalAtletas) * 100, 1) : 0;

// --------------------------------------------------------------------------
// METRICA 2: INVENTARIO DE PLAYERAS (ENTREGADAS VS DISPONIBLES EN EVENTO)
// --------------------------------------------------------------------------
// 1. Extraemos el stock total límite configurado para este evento específico
$sqlLimites = "SELECT stock_xs, stock_ch, stock_m, stock_g, stock_xg, stock_2xl FROM tbl_eventos WHERE id = ?";
$qLimites = sqlsrv_query($conn, $sqlLimites, array($evento_id));
$rLimites = sqlsrv_fetch_array($qLimites, SQLSRV_FETCH_ASSOC);

$limiteXS  = $rLimites['stock_xs'] ?? 0;
$limiteCH  = $rLimites['stock_ch'] ?? 0;
$limiteM   = $rLimites['stock_m'] ?? 0;
$limiteG   = $rLimites['stock_g'] ?? 0;
$limiteXG  = $rLimites['stock_xg'] ?? 0;
$limite2XL = $rLimites['stock_2xl'] ?? 0;

// 2. Contamos cuántas prendas de cada talla han salido físicamente del almacén con firma
$sqlTallas = "SELECT talla_playera, COUNT(*) as cantidad FROM tbl_entregas_kits WHERE evento_id = ? GROUP BY talla_playera";
$qTallas = sqlsrv_query($conn, $sqlTallas, array($evento_id));

// Mapeamos las cantidades entregadas por defecto en 0
$entregadas = ['XS' => 0, 'CH' => 0, 'M' => 0, 'G' => 0, 'XG' => 0, '2XL' => 0];
while ($row = sqlsrv_fetch_array($qTallas, SQLSRV_FETCH_ASSOC)) {
    $tallaUpper = strtoupper(trim($row['talla_playera']));
    if (array_key_exists($tallaUpper, $entregadas)) {
        $entregadas[$tallaUpper] = $row['cantidad'];
    }
}

// 3. Empaquetamos la estructura cruzada para entregarla limpia al Frontend
$tallas = [
    'XS'  => ['entregados' => $entregadas['XS'],  'total' => $limiteXS],
    'CH'  => ['entregados' => $entregadas['CH'],  'total' => $limiteCH],
    'M'   => ['entregados' => $entregadas['M'],   'total' => $limiteM],
    'G'   => ['entregados' => $entregadas['G'],   'total' => $limiteG],
    'XG'  => ['entregados' => $entregadas['XG'],  'total' => $limiteXG],
    '2XL' => ['entregados' => $entregadas['2XL'], 'total' => $limite2XL]
];

// --------------------------------------------------------------------------
// METRICA 3: PRODUCTIVIDAD DEL STAFF EN ESTE EVENTO
// --------------------------------------------------------------------------
$sqlStaff = "SELECT u.nombre as operador, 
                    COUNT(e.competidor_id) as total_entregas,
                    --CALCULO DE LATIDO EN TIEMPO REAL
                    CASE 
                        WHEN u.ultima_actividad >= DATEADD(minute, -3, GETDATE()) THEN 'Activo'
                        ELSE 'Ausente'
                    END as estatus_operativo
             FROM tbl_usuarios u
             INNER JOIN tbl_entregas_kits e ON u.id = e.staff_id
             WHERE e.evento_id = ?
             GROUP BY u.nombre, u.ultima_actividad --Obligatorio incluir ultima_actividad aquí
             ORDER BY total_entregas DESC";

$qStaff = sqlsrv_query($conn, $sqlStaff, array($evento_id));

$rankingStaff = [];
while ($row = sqlsrv_fetch_array($qStaff, SQLSRV_FETCH_ASSOC)) {
    $rankingStaff[] = $row;
}

// RESPUESTA LIMPIA EN FORMATO JSON (Se mantiene exactamente igual)
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
