<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: config/db.php (CONEXIÓN CENTRALIZADA A SQL SERVER 2014)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

// 1. Parámetros de configuración del servidor local
// Usamos la instancia local SQLEXPRESS que configuraste con éxito en SSMS
$serverName = "localhost\\SQLEXPRESS";

// 2. Opciones de conexión
$connectionInfo = array(
    "Database" => "db_proyecto_deportivo", // Tu base de datos recién creada
    "CharacterSet" => "UTF-8"              // CRÍTICO: Para soportar acentos y eñes de los atletas
);

// 3. Intentar realizar el puente de conexión nativa
$conn = sqlsrv_connect($serverName, $connectionInfo);

// 4. Verificación de seguridad del canal
if (!$conn) {
    echo "<h3>Error crítico: No se pudo conectar a la base de datos SQL Server.</h3>";
    echo "<p>Detalles del error:</p><pre>";
    // Desglosa el error exacto de Microsoft si algo falla
    print_r(sqlsrv_errors());
    echo "</pre>";
    die(); // Detiene la ejecución del sistema por seguridad
}

// Nota: Si el archivo no arroja errores, la variable $conn queda disponible 
// para realizar consultas SELECT, INSERT y UPDATE en tus APIs.