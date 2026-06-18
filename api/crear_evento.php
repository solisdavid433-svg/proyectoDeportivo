<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/crear_evento.php (VERSIÓN BLINDADA CON OUTPUT INSERTED)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();


/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php';

// Candado estricto de seguridad
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

// 1. CAPTURA Y VALIDACIÓN DE DATOS GENERALES
$nombre_evento = isset($_POST['nombre_evento']) ? trim($_POST['nombre_evento']) : '';
$fecha_evento  = isset($_POST['fecha_evento']) ? trim($_POST['fecha_evento']) : '';
$disciplina    = isset($_POST['disciplina_evento']) ? trim($_POST['disciplina_evento']) : '';

if (empty($nombre_evento) || empty($fecha_evento) || empty($disciplina)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos generales son obligatorios.']);
    exit;
}

if (!isset($_FILES['file_csv']) || $_FILES['file_csv']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Es obligatorio adjuntar el archivo CSV.']);
    exit;
}

$fileTmpPath = $_FILES['file_csv']['tmp_name'];
if (strtolower(pathinfo($_FILES['file_csv']['name'], PATHINFO_EXTENSION)) !== 'csv') {
    echo json_encode(['success' => false, 'message' => 'Solo se admiten archivos .csv']);
    exit;
}

// 2. INCIAMOS LA TRANSACCIÓN ATÓMICA
sqlsrv_begin_transaction($conn);

// 💥 FIJATE AQUÍ: Unificamos el INSERT con OUTPUT INSERTED.id para atrapar el ID en el acto
$sqlInsertEvento = "INSERT INTO tbl_eventos (nombre_evento, fecha, disciplina) 
                    OUTPUT INSERTED.id 
                    VALUES (?, ?, ?)";

$stmtEvento = sqlsrv_query($conn, $sqlInsertEvento, array($nombre_evento, $fecha_evento, $disciplina));

if ($stmtEvento === false) {
    sqlsrv_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Error al registrar el evento en SQL Server.']);
    exit;
}

// Leemos el ID que la consulta nos escupió de inmediato
$rowId = sqlsrv_fetch_array($stmtEvento, SQLSRV_FETCH_ASSOC);
$evento_id_asignado = isset($rowId['id']) ? intval($rowId['id']) : 0;

// Validación de control
if ($evento_id_asignado === 0) {
    sqlsrv_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'No se pudo recuperar la clave relacional mediante OUTPUT.']);
    exit;
}

// 3. PROCESAMIENTO SECUENCIAL DEL PADRÓN DE COMPETIDORES
if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {

    // Brincamos la primera fila de encabezados (folio, nombre, categoria)
    fgetcsv($handle, 1000, ",");

    $atletas_insertados = 0;
    $errores_csv = 0;

    $sqlAtleta = "INSERT INTO tbl_competidores (folio, nombre, categoria, estatus_entrega, evento_id) 
                  VALUES (?, ?, ?, 'PENDIENTE', ?)";

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if (empty($data[0])) continue;

        $folio     = intval($data[0]);
        $nombre    = trim($data[1]);
        $categoria = trim($data[2]);

        $paramsAtleta = array($folio, $nombre, $categoria, $evento_id_asignado);
        $stmtAtleta = sqlsrv_query($conn, $sqlAtleta, $paramsAtleta);

        if ($stmtAtleta === false) {
            $errores_csv++;
        } else {
            $atletas_insertados++;
        }
    }
    fclose($handle);

    // 4. VERIFICACIÓN Y VALIDACIÓN FINAL DE LA TRANSACCIÓN
    if ($errores_csv === 0 && $atletas_insertados > 0) {
        sqlsrv_commit($conn);
        echo json_encode([
            'success' => true,
            'message' => "La competencia fue creada e indexada con éxito total en el servidor.",
            'inserted_athletes' => $atletas_insertados
        ]);
    } else {
        sqlsrv_rollback($conn);
        $msgErr = ($atletas_insertados === 0)
            ? "El archivo CSV parece estar vacío o no contiene el formato esperado."
            : "Se detectaron {$errores_csv} errores estructurales en el padrón. Creación de evento abortada.";

        echo json_encode(['success' => false, 'message' => $msgErr]);
    }
} else {
    sqlsrv_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'No se pudo abrir el buffer de lectura temporal en Apache.']);
}
