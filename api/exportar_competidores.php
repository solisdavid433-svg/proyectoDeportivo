<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/exportar_competidores.php (PRODUCCIÓN - AUDITORÍA COMPLETA)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */

require_once '../config/db.php';

// Candado estricto: Solo el Administrador puede descargar bases de datos completas
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    die("Acceso denegado. No tiene permisos de auditoría.");
}

// Capturamos el ID del circuito enviado desde el botón de la cabecera
$evento_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($evento_id === 0) {
    die("Error: ID de competencia no válido.");
}

// 1. Consultamos el nombre del evento para personalizar el nombre del archivo final
$nombre_evento = "Reporte";
$sql_ev = "SELECT nombre_evento FROM tbl_eventos WHERE id = ?";
$stmt_ev = sqlsrv_query($conn, $sql_ev, array($evento_id));
if ($stmt_ev && $row_ev = sqlsrv_fetch_array($stmt_ev, SQLSRV_FETCH_ASSOC)) {
    $nombre_evento = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row_ev['nombre_evento']);
}

// 2. FORZADO DE DESCARGA: Configuramos las cabeceras HTTP para descargar el archivo Excel (.xls)
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="Auditoria_Kits_' . $nombre_evento . '_' . date('Ymd_His') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// BLINDAJE DE ACENTOS: Inyectamos el BOM UTF-8 para evitar caracteres rotos en Windows Excel
echo "\xEF\xBB\xBF";

// 3. CONSULTA RELACIONAL ADAPTADA PERFECTAMENTE A TU ESQUEMA EN VIVO
$sql = "SELECT c.folio, c.nombre, c.categoria, c.estatus_entrega, 
               e.fecha_entrega, e.hubo_cambio, e.talla_playera,
               u.nombre AS operador_entrega
        FROM tbl_competidores c
        LEFT JOIN tbl_entregas_kits e ON c.folio = e.competidor_id AND c.evento_id = e.evento_id
        LEFT JOIN tbl_usuarios u ON e.staff_id = u.id
        WHERE c.evento_id = ?
        ORDER BY c.folio ASC";

$stmt = sqlsrv_query($conn, $sql, array($evento_id));

if ($stmt === false) {
    die("Error crítico de consistencia al compilar el reporte. Verifique la conexión a SQL Server.");
}
?>

<table border="1">
    <thead>
        <tr style="background-color: #1E40AF; color: white; font-weight: bold; text-align: center;">
            <th style="padding: 8px; width: 100px;">Folio / Chip</th>
            <th style="padding: 8px; width: 320px;">Nombre Completo del Atleta</th>
            <th style="padding: 8px; width: 160px;">Categoría</th>
            <th style="padding: 8px; width: 90px;">Talla Kit</th>
            <th style="padding: 8px; width: 140px;">Estatus Entrega</th>
            <th style="padding: 8px; width: 210px;">Fecha y Hora de Reclamo</th>
            <th style="padding: 8px; width: 220px;">Operador de Validación (Mesa)</th>
            <th style="padding: 8px; width: 140px;">Estatus Incidencia</th>
        </tr>
    </thead>
    <tbody>
        <?php
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $estatus = strtoupper($row['estatus_entrega'] ?? 'PENDIENTE');
            $incidencia = ($row['hubo_cambio'] == 1) ? 'SÍ (Modificado)' : 'NO';

            // Tratamiento de fechas seguro para objetos DateTime de SQL Server
            $fecha_texto = '--';
            if (isset($row['fecha_entrega']) && $row['fecha_entrega'] instanceof DateTime) {
                $fecha_texto = $row['fecha_entrega']->format('d/m/Y h:i:s A');
            }

            $operador = !empty($row['operador_entrega']) ? $row['operador_entrega'] : '--';
            $talla_prenda = !empty($row['talla_playera']) ? strtoupper($row['talla_playera']) : 'S/T';

            // Resaltador visual verde claro para paquetes ya entregados
            $bg_fila = ($estatus === 'ENTREGADO') ? 'style="background-color: #E8F5E9;"' : '';
        ?>
            <tr <?php echo $bg_fila; ?>>
                <td style="text-align: center; font-weight: bold;">#<?php echo $row['folio']; ?></td>
                <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                <td style="text-align: center;"><?php echo htmlspecialchars($row['categoria']); ?></td>
                <td style="text-align: center; font-weight: 600;"><?php echo $talla_prenda; ?></td>
                <td style="text-align: center; font-weight: bold; color: <?php echo ($estatus === 'ENTREGADO') ? '#2E7D32' : '#475569'; ?>;">
                    <?php echo $estatus; ?>
                </td>
                <td style="text-align: center; color: #1E293B;"><?php echo $fecha_texto; ?></td>
                <td style="text-align: left; padding-left: 6px;"><?php echo htmlspecialchars($operador); ?></td>
                <td style="text-align: center; font-weight: 500; color: <?php echo ($row['hubo_cambio'] == 1) ? '#C62828' : '#475569'; ?>;">
                    <?php echo $incidencia; ?>
                </td>
            </tr>
        <?php
        }
        ?>
    </tbody>
</table>