<?php
// CONEXIÓN CENTRALIZADA A SQL SERVER
// 1. Parámetros de configuración del servidor local
$serverName = "localhost\\SQLEXPRESS";

// 2. Opciones de conexión
$connectionInfo = array(
    "Database" => "db_proyecto_deportivo",
    "CharacterSet" => "UTF-8"
);

// 3. Intentar realizar el puente de conexión nativa
$conn = sqlsrv_connect($serverName, $connectionInfo);

// 4. Verificación de seguridad del canal
if (!$conn) {
    // Si la petición viene vía AJAX/Fetch (Login, API, etc.), respondemos en JSON puro
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || isset($_POST['usuario'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Error de conexión a la base de datos SQL Server.',
            'errors'  => sqlsrv_errors()
        ]);
        exit;
    } else {
        // Si entras directo desde la URL del navegador
        echo "<h3>Error crítico: No se pudo conectar a la base de datos SQL Server.</h3>";
        echo "<p>Detalles del error:</p><pre>";
        print_r(sqlsrv_errors());
        echo "</pre>";
        die();
    }
}

// Si el usuario tiene una sesión activa, actualizamos su "latido" en la base de datos
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario_id'])) {
    $sql_heartbeat = "UPDATE tbl_usuarios SET ultima_actividad = GETDATE() WHERE id = ?";
    @sqlsrv_query($conn, $sql_heartbeat, array($_SESSION['usuario_id']));
}
