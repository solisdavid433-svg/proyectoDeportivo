<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: api/crear_evento.php (PRODUCCIÓN - ADAPTADO AL ESQUEMA REAL)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

/** @var resource $conn */

header('Content-Type: application/json');
require_once '../config/db.php'; // Tu conexión limpia a SQL Server

// 1. CAPTURAMOS LOS DATOS DEL FORMULARIO EN VIVO
$nombre_evento = $_POST['nombre_evento'] ?? $_POST['nombre'] ?? '';
$fecha_evento  = $_POST['fecha'] ?? date('Y-m-d');
$disciplina    = $_POST['disciplina'] ?? 'Carrera';

if (empty($nombre_evento)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, ingrese el nombre del evento.']);
    exit;
}

if (!isset($_FILES['archivo_corredores']) || $_FILES['archivo_corredores']['error'] !== 0) {
    echo json_encode(['success' => false, 'message' => 'Por favor, adjunte el archivo Excel .xlsx del padrón.']);
    exit;
}

// ==========================================================================
// PASO A: REGISTRAR EL NUEVO EVENTO ADAPTADO A TU ESQUEMA DE COLUMNAS
// ==========================================================================
sqlsrv_begin_transaction($conn); // Iniciamos el escudo protector (Transacción)

// 🎯 INGENIERÍA AJUSTADA: Insertamos todas las columnas obligatorias 'No NULL' detectadas
$sql_evento = "INSERT INTO tbl_eventos 
               (nombre_evento, fecha, disciplina, stock_xs, stock_ch, stock_m, stock_g, stock_xg, stock_2xl, es_activo) 
               OUTPUT INSERTED.id 
               VALUES (?, ?, ?, 0, 0, 0, 0, 0, 0, 1)";

$stmt_evento = sqlsrv_query($conn, $sql_evento, array($nombre_evento, $fecha_evento, $disciplina));

if ($stmt_evento === false) {
    sqlsrv_rollback($conn);

    $lista_errores = [];
    if (($errores_sql = sqlsrv_errors()) != null) {
        foreach ($errores_sql as $err) {
            $lista_errores[] = "Cód [" . $err['code'] . "]: " . $err['message'];
        }
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error Estructural de SQL Server: ' . implode(' || ', $lista_errores)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Atrapamos el ID autoincrementable que generó la base de datos para esta carrera
sqlsrv_fetch($stmt_evento);
$nuevo_evento_id = sqlsrv_get_field($stmt_evento, 0);


// ==========================================================================
// PASO B: PROCESAR EL ARCHIVO EXCEL (.XLSX) NATIVO EN MEMORIA
// ==========================================================================
$rutaTemporal = $_FILES['archivo_corredores']['tmp_name'];
$zip = new ZipArchive;

if ($zip->open($rutaTemporal) === TRUE) {

    // Extracción de textos indexados de Excel
    $sharedStrings = [];
    if ($stringsXml = $zip->getFromName('xl/sharedStrings.xml')) {
        $xmlStrings = simplexml_load_string($stringsXml);
        foreach ($xmlStrings->si as $si) {
            if (isset($si->t)) {
                $sharedStrings[] = (string)$si->t;
            } else if (isset($si->r)) {
                $textParts = [];
                foreach ($si->r as $rPart) {
                    $textParts[] = (string)$rPart->t;
                }
                $sharedStrings[] = implode('', $textParts);
            } else {
                $sharedStrings[] = '';
            }
        }
    }

    // Procesamiento de celdas en la Hoja 1
    if ($sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml')) {
        $xmlSheet = simplexml_load_string($sheetXml);
        $filasData = [];
        $encabezados = [];
        $esPrimeraFila = true;

        foreach ($xmlSheet->sheetData->row as $row) {
            $filaActual = [];
            foreach ($row->c as $cell) {
                preg_match('/([A-Z]+)/', (string)$cell['r'], $matches);
                $colLetra = $matches[1] ?? '';
                $val = (string)$cell->v;
                if (isset($cell['t']) && (string)$cell['t'] === 's') {
                    $val = $sharedStrings[intval($val)] ?? '';
                }
                if (!empty($colLetra)) {
                    $filaActual[$colLetra] = trim($val);
                }
            }
            if ($esPrimeraFila) {
                $encabezados = $filaActual;
                $esPrimeraFila = false;
            } else {
                $filasData[] = $filaActual;
            }
        }

        // Mapeo dinámico de las columnas del archivo del señor Carlos
        $letraFolio     = array_search('Ficha', $encabezados);
        $letraCategoria = array_search('CategoryName', $encabezados);

        // BUSCADOR INTELIGENTE DE NOMBRE: Busca la columna bajo cualquiera de sus dos variantes conocidas
        $letraNombre    = array_search('Participant', $encabezados);
        if ($letraNombre === false) {
            $letraNombre = array_search('ParticipantName', $encabezados); // Variante del archivo anterior
        }

        // Validamos que por lo menos existan el folio y el nombre para poder operar
        if ($letraFolio === false || $letraNombre === false || $letraCategoria === false) {
            sqlsrv_rollback($conn);
            echo json_encode([
                'success' => false,
                'message' => 'Estructura de Excel inválida. Asegúrese de que el archivo incluya las columnas: Ficha, CategoryName y la columna de Nombre (Participant o ParticipantName).'
            ]);
            $zip->close();
            exit;
        }

        $errores = 0;
        $insertados = 0;

        // Recorremos e insertamos en caliente amarrando los folios al nuevo evento_id
        foreach ($filasData as $fila) {
            $folio     = isset($fila[$letraFolio]) ? intval($fila[$letraFolio]) : 0;
            $nombre    = $fila[$letraNombre] ?? '';
            $categoria = $fila[$letraCategoria] ?? '';

            if ($folio === 0 || empty($nombre) || empty($categoria)) continue;

            $sql_comp = "INSERT INTO tbl_competidores (folio, nombre, categoria, estatus_entrega, evento_id) 
                         VALUES (?, ?, ?, 'PENDIENTE', ?)";
            $stmt_comp = sqlsrv_query($conn, $sql_comp, array($folio, $nombre, $categoria, $nuevo_evento_id));

            if ($stmt_comp === false) {
                $errores++;
            } else {
                $insertados++;
            }
        }

        // Cierre y validación del bloque atómico
        if ($errores > 0) {
            sqlsrv_rollback($conn);
            echo json_encode(['success' => false, 'message' => "Se detectaron {$errores} fallas al registrar el padrón en la base de datos."]);
        } else {
            sqlsrv_commit($conn); // Guardamos la carrera y los corredores juntos de forma segura
            echo json_encode(['success' => true, 'message' => "¡Éxito! Competencia registrada e importados {$insertados} atletas."]);
        }
    } else {
        sqlsrv_rollback($conn);
        echo json_encode(['success' => false, 'message' => 'No se pudo leer la matriz de celdas del Excel.']);
    }
    $zip->close();
} else {
    sqlsrv_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'No se pudo abrir el archivo .xlsx de forma binaria.']);
}
