<?php
// Inicializamos el sistema de sesiones para saber a quién destruir
session_start();

// Si el usuario que está cerrando sesión es el Encargado, apagamos el circuito en la BD
if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Encargado') {

    // 🎯 CORRECCIÓN DE RUTA 1: Subimos un nivel con "../" para alcanzar la carpeta config
    require_once '../config/db.php';

    if (isset($conn)) {
        $sqlApagar = "UPDATE tbl_eventos SET es_activo = 0";
        sqlsrv_query($conn, $sqlApagar);
    }
}

// Borramos todas las variables de la sesión
$_SESSION = array();

// Destruimos la sesión en el servidor por completo
session_destroy();

// Redirigimos al usuario al formulario de inicio de sesión limpio
header('Location: ../index.php');
exit();
