<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: logout.php (CIERRE DE SESIÓN SEGURO CON APAGADO GLOBAL)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

session_start();

// Si el usuario que está cerrando sesión es el Encargado, apagamos el circuito en la BD
if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Encargado') {

    // Incluimos la conexión a la base de datos (Ajusta la ruta si tu logout está dentro de api/)
    require_once 'config/db.php';

    if (isset($conn)) {
        $sqlApagar = "UPDATE tbl_eventos SET es_activo = 0";
        sqlsrv_query($conn, $sqlApagar);
    }
}

// Destruimos todas las variables de la sesión local
$_SESSION = array();

// Si se usan cookies de sesión, las invalidamos en el navegador
if (ini_get("session_use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruimos la sesión en el servidor
session_destroy();

// Redirigimos al formulario de Login principal
header("Location: index.php");
exit();
