<?php
// ==========================================================================
// SYSTEM: CONTROL DE ENTREGA DE KITS DEPORTIVOS
// FILE: config/seed.php (CREACIÓN DE USUARIOS DE PRUEBA)
// AUTHOR: JOSÉ DAVID SOLÍS RANGEL
// ==========================================================================

// Incluimos la conexión que acabamos de probar con éxito
require_once 'db.php';

/** @var resource $conn */

echo "<h2>Generando usuarios de prueba...</h2>";

// Arreglo con los 3 usuarios para los 3 módulos del sistema
$usuarios = [
    [
        'nombre' => 'José David Admin',
        'correo' => 'admin@proyecto.com',
        'password' => 'admin123',
        'rol' => 'Administrador'
    ],
    [
        'nombre' => 'Reymundo Supervisor',
        'correo' => 'encargado@proyecto.com',
        'password' => 'encargado123',
        'rol' => 'Encargado'
    ],
    [
        'nombre' => 'Staff Mesa 1',
        'correo' => 'staff@proyecto.com',
        'password' => 'staff123',
        'rol' => 'Staff'
    ]
];

foreach ($usuarios as $u) {
    // Ciframos la contraseña de forma segura usando el algoritmo estándar de PHP (Bcrypt)
    $passwordCifrada = password_hash($u['password'], PASSWORD_DEFAULT);

    // Preparamos la consulta SQL para SQL Server
    $sql = "INSERT INTO tbl_usuarios (nombre, correo, password, rol, estatus) 
            VALUES (?, ?, ?, ?, 1)";

    // Pasamos los parámetros de forma segura para evitar Inyección SQL
    $params = array($u['nombre'], $u['correo'], $passwordCifrada, $u['rol']);

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        echo "<p style='color: green;'>✔️ Usuario [{$u['rol']}] creado con éxito. Correo: <b>{$u['correo']}</b> | Contraseña original: <b>{$u['password']}</b></p>";
    } else {
        echo "<p style='color: red;'>❌ Error al crear usuario [{$u['rol']}]: </p><pre>";
        print_r(sqlsrv_errors());
        echo "</pre>";
    }
}
