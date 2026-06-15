<?php
// Inicializamos el sistema de sesiones para saber a quién destruir
session_start();

// Borramos todas las variables de la sesión
$_SESSION = array();

// Destruimos la sesión en el servidor por completo
session_destroy();

// Redirigimos al usuario al formulario de inicio de sesión limpio
header('Location: ../index.php');
exit();
