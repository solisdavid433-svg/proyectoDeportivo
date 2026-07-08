<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/exportar_cronometraje.php (PLANTILLA PARA SOFTWARE DE CHIPS)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */

require_once '../config/db.php';

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    die("Acceso denegado.");
}

$evento_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($evento_id === 0) die("ID no válido.");

// Cabeceras para forzar la descarga de Excel limpio
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="Plantilla_Cronometraje_ID_' . $evento_id . '.xls"');

// BOM para acentos perfectos
echo "\xEF\xBB\xBF";

// Jalamos los competidores inscritos en esta carrera
$sql = "SELECT c.folio, c.nombre, c.categoria, e.talla_playera
        FROM tbl_competidores c
        LEFT JOIN tbl_entregas_kits e ON c.folio = e.competidor_id AND c.evento_id = e.evento_id
        WHERE c.evento_id = ?
        ORDER BY c.folio ASC";

$stmt = sqlsrv_query($conn, $sql, array($evento_id));
?>

<table border="1">
    <thead>
        <!-- Encabezados idénticos a los que exporta el software del señor Carlos -->
        <tr style="background-color: #334155; color: white; font-weight: bold;">
            <th>Ficha</th>
            <th>Bib</th>
            <th>ParticipantName</th>
            <th>RouteName</th>
            <th>CategoryName</th>
            <th>AthleteId</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $talla = !empty($row['talla_playera']) ? strtoupper($row['talla_playera']) : 'S/T';
        ?>
            <tr>
                <!-- Duplicamos el folio en Ficha y Bib para garantizar coincidencia de chip -->
                <td style="text-align: center; font-weight: bold;"><?php echo $row['folio']; ?></td>
                <td style="text-align: center; font-weight: bold;"><?php echo $row['folio']; ?></td>
                <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                <td style="text-align: center;">CIRCUITO</td>
                <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                <!-- Inyectamos la talla en el AthleteId tal como lo maneja su programa -->
                <td style="text-align: center;"><?php echo $talla; ?></td>
                <td style="text-align: center;">Active</td>
            </tr>
        <?php
        }
        ?>
    </tbody>
</table>