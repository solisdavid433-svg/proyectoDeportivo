<?php
// Comprobamos si PHP ya reconoce la extensión
if (extension_loaded('sqlsrv')) {
    echo "¡Felicidades! La extensión SQLSRV está activa y funcionando.";
} else {
    echo "La extensión sigue sin cargar. Revisa el ODBC Driver o los nombres del php.ini";
}
