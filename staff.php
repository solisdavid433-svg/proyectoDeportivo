<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesa de Entrega (Staff) - Proyecto Deportivo</title>
    <link rel="stylesheet" href="public/css/styles.css">
</head>

<body class="bg-light">

    <header class="navbar sticky-navbar">
        <div class="navbar-brand">
            <img src="public/img/logo.png" alt="Proyecto Deportivo" class="nav-logo">
            <span class="event-title-active">Morelia 10K - Entrega de Kits</span>
        </div>
        <div class="navbar-user">
            <span class="user-badge staff-mode">Staff (Mesa 1)</span>
            <button id="btn-logout" class="btn-logout">Salir</button>
        </div>
    </header>

    <main class="staff-container">

        <section class="search-section">
            <div class="search-box-wrapper">
                <input type="text" id="search-input" class="search-input-giant" placeholder="🔍 Busque por Nombre, Folio o Número de Competidor..." autocomplete="off">
            </div>
            <button id="btn-trigger-qr" class="btn btn-secondary btn-qr">
                <span class="qr-icon">📷</span> Escanear QR
            </button>
        </section>

        <section id="qr-scanner-container" class="qr-scanner-box hidden">
            <div class="qr-scanner-header">
                <h3>Escáner de Código QR Activo</h3>
                <button id="btn-close-qr" class="btn-close-sm">✕</button>
            </div>
            <div id="camera-preview" class="camera-preview-window">
                <p class="camera-placeholder">Esperando acceso a la cámara de la tableta...</p>
            </div>
        </section>

        <div class="table-responsive border-card">
            <table class="admin-table table-staff-aligned">
                <thead>
                    <tr>
                        <th>Folio / No.</th>
                        <th>Nombre Completo</th>
                        <th>Categoría / Disciplina</th>
                        <th>Estatus</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody id="table-body-participants">

                    <tr>
                        <td><strong>#1024</strong></td>
                        <td class="td-athlete-name">José David Solís Rangel</td>
                        <td>Libre Varonil (10K)</td>
                        <td><span class="badge-status-kit pending">Pendiente</span></td>
                        <td class="text-center">
                            <button class="btn btn-primary btn-action-kit" data-id="1024" data-name="José David Solís Rangel">
                                Entregar Kit
                            </button>
                        </td>
                    </tr>

                    <tr class="row-completed">
                        <td><strong>#1025</strong></td>
                        <td class="td-athlete-name">Carlos Cruz García</td>
                        <td>Master Varonil (10K)</td>
                        <td><span class="badge-status-kit delivered">Entregado</span></td>
                        <td class="text-center">
                            <button class="btn btn-disabled btn-action-kit" disabled>✔️ Entregado</button>
                        </td>
                    </tr>

                    <tr class="row-changed-active">
                        <td><strong>#1026</strong></td>
                        <td class="td-athlete-name">Stephanie Villanueva Cruz</td>
                        <td>Libre Femenil (10K)</td>
                        <td><span class="badge-status-kit delivered">Entregado</span></td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-action-kit" data-id="1026" data-name="Stephanie Villanueva Cruz">
                                ⚠️ Hacer cambio
                            </button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </main>

    <div id="modal-signature-overlay" class="modal-overlay hidden">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Confirmación de Conformidad y Firma</h3>
            </div>

            <div class="modal-body">
                <p class="modal-instruction">Pida al atleta verificar sus datos y plasmar su firma manuscrita en el recuadro blanco.</p>

                <div class="modal-athlete-summary">
                    <p><strong>Competidor:</strong> <span id="modal-text-name">José David Solís Rangel</span></p>
                    <p><strong>Folio:</strong> <span id="modal-text-id">#1024</span></p>
                </div>

                <div class="canvas-signature-wrapper">
                    <canvas id="signature-canvas" width="450" height="200"></canvas>
                    <p class="canvas-caption">Área de captura digital de firma</p>
                </div>
            </div>

            <div class="modal-footer">
                <button id="btn-clear-canvas" class="btn btn-secondary-outline btn-sm">Limpiar Trazo</button>
                <button id="btn-cancel-signature" class="btn btn-secondary-outline btn-sm">Cancelar</button>
                <button id="btn-confirm-delivery" class="btn btn-success">Validar y Firmar</button>
            </div>
        </div>
    </div>

    <script src="public/js/staff.js"></script>
</body>

</html>