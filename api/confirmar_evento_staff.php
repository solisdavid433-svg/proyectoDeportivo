<?php
session_start();
if (isset($_SESSION['temp_evento_id'])) {
    // Fijamos el ID del evento de forma oficial en la sesión del Staff
    $_SESSION['evento_id_staff'] = $_SESSION['temp_evento_id'];
    unset($_SESSION['temp_evento_id']);
}
header('Location: ../staff.php');
exit();
