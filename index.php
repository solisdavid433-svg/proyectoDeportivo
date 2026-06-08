<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Proyecto Deportivo</title>
    <!-- Ruta adaptada a la nueva infraestructura local -->
    <link rel="stylesheet" href="public/css/styles.css">
</head>

<body class="login-body">

    <div class="login-container">
        <!-- Encabezado con Identidad Corporativa -->
        <div class="login-logo-wrapper">
            <!-- Recuerda colocar tu imagen en public/img/logo.png -->
            <img src="public/img/logo.png" alt="Logo Proyecto Deportivo" class="main-logo">
        </div>

        <h2 class="login-title">Control de Entrega de Kits</h2>
        <p class="login-subtitle">Inicie sesión para acceder a su módulo operativo</p>

        <!-- Formulario enfocado a procesamiento asíncrono con JavaScript (Fetch) -->
        <form id="form-login" class="login-form" autocomplete="off" onsubmit="return false;">

            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" placeholder="nombre@dominio.com" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <!-- Botón Fat-Finger adaptado para el uso ágil en tabletas -->
            <button type="submit" id="btn-submit-login" class="btn btn-primary btn-block">
                Ingresar al Sistema
            </button>
        </form>
    </div>

    <!-- Vinculación de lógica limpia de interacción asíncrona -->
    <script src="public/js/auth.js"></script>
</body>

</html>