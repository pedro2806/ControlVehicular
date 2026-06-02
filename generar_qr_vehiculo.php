<?php
session_start();
include 'conn.php';
if (empty($_COOKIE['noEmpleado'])) {
    header('location: index');
    exit;
}
// Verificar acceso especial 'verQR'
$noEmpleado = intval($_COOKIE['noEmpleado']);
$stmt = $conn->prepare(
    "SELECT 1 FROM mess_rrhh.accesos_especiales
     WHERE noEmpleado = ? AND sistema = 'ctrlVehicular' AND opcion = 'verQR' AND estatus = 1
     LIMIT 1"
);
$stmt->bind_param("i", $noEmpleado);
$stmt->execute();
$tieneAcceso = (bool) $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$tieneAcceso) {
    header('location: inicio');
    exit;
}
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$baseUrl   = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Control Vehicular - Generar QR</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>
        /* ── Sticker: estilos compartidos entre preview y lote ──
           El cuerpo y el footer son TRANSPARENTES para que al imprimirse en
           vinil transparente, solo se vea tinta donde hay color.
           El area del QR si tiene fondo blanco explicito: garantiza el
           contraste necesario para que cualquier lector escanee el codigo
           (zona segura / quiet zone) y que el logo MESS no se pierda. */
        .sticker-item {
            display: inline-flex;
            flex-direction: column;
            border: 2.5px solid #050D9E;
            border-radius: 8px;
            overflow: hidden;
            background: transparent;
            width: 340px;
            font-family: Arial, sans-serif;
        }
        .sticker-body {
            display: flex;
            flex-direction: row;
            align-items: center;
            padding: 12px 16px;
            gap: 16px;
        }
        .sticker-qr {
            flex-shrink: 0;
            position: relative;
            background: #ffffff;     /* imprime como tinta blanca sobre vinil transparente */
            padding: 3mm;
            border-radius: 2mm;
        }
        .sticker-qr canvas, .sticker-qr img:not(.logo-qr) { display: block; }
        .logo-qr {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 40px; height: 40px;
            background: #ffffff;
            border-radius: 4px;
            padding: 1px;
            pointer-events: none;
            z-index: 1;
        }
        .sticker-info { flex: 1; text-align: left; }
        .sticker-nombre {
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .sticker-anio {
            font-size: 1rem;
            font-weight: 700;
            color: #222;
        }
        .sticker-sep { margin: 8px 0; border-color: #333; }
        .sticker-label { font-size: 0.75rem; color: #444; line-height: 1.4; }
        .sticker-mess  { font-size: 0.88rem; font-weight: 700; color: #222; }
        .sticker-footer {
            border-top: 1.5px solid #050D9E;
            padding: 5px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: transparent;
        }
        .sticker-soporte    { font-size: 0.62rem; color: #555; }
        .sticker-placa-footer { font-size: 0.78rem; font-weight: 700; letter-spacing: 1px; color: #222; }

        /* ── Lote: grid en pantalla ── */
        #loteGrid {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: flex-start;
        }
        .btn-quitar-sticker {
            font-size: 0.68rem;
            border-radius: 0;
        }

        /* ── Impresión ── */
        @page {
            size: letter;
            margin: 5mm;
        }
        @media print {
            .no-print { display: none !important; }

            /* Ocultar chrome del layout SB-Admin-2 */
            #accordionSidebar,
            .topbar,
            .scroll-to-top,
            footer { display: none !important; }

            /* Limpiar espacios de los wrappers para que no empujen el contenido */
            #wrapper, #content-wrapper, #content, .container-fluid,
            #loteContainer, #loteContainer > .card, #loteContainer .card-body,
            #areaImpresionLote {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                max-height: none !important;
                overflow: visible !important;
                height: auto !important;
                width: 100% !important;
            }

            #loteGrid {
                display: grid;
                grid-template-columns: repeat(2, 95mm);
                gap: 3mm;
                justify-content: center;
            }
            .sticker-item {
                width: 95mm;
                height: 53mm;
                box-sizing: border-box;
                overflow: hidden;
                break-inside: avoid;
                display: inline-flex !important;
            }
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'menu.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-3 text-black-800 no-print">Generar QR por Vehículo</h1>

                    <!-- Tabla de vehículos -->
                    <div class="card shadow mb-4 no-print">
                        <div class="card-header bg-white py-2 d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-bold text-dark small">Vehículos</span>
                            <button id="btnAgregarSeleccionados"
                                class="btn btn-success btn-sm"
                                style="display:none;"
                                onclick="agregarSeleccionados()">
                                <i class="fas fa-layer-group me-1"></i>
                                Agregar seleccionados (<span id="cntSeleccionados">0</span>)
                            </button>
                            <div class="ms-auto d-flex align-items-center gap-2" id="botonesLote" style="display:none !important;">
                                <span class="text-muted small">Lote:</span>
                                <span class="badge bg-success" id="loteContador">0</span>
                                <button class="btn btn-outline-danger btn-sm" onclick="limpiarLote()">
                                    <i class="fas fa-trash me-1"></i> Limpiar
                                </button>
                                <button class="btn btn-success btn-sm" onclick="imprimirLote()">
                                    <i class="fas fa-print me-1"></i> Imprimir lote
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="TablaVehiculos" class="table table-bordered table-striped w-100">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th style="width:40px;" class="text-center">
                                                <input type="checkbox" id="chkTodos" title="Seleccionar todos">
                                            </th>
                                            <th>Placa</th>
                                            <th>Modelo</th>
                                            <th>Marca</th>
                                            <th>Color</th>
                                            <th>Año</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Lote de impresión -->
                    <div id="loteContainer" style="display:none;">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div id="areaImpresionLote">
                                    <div id="loteGrid"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; MESS <?php echo date("Y"); ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        var baseUrl       = <?php echo json_encode($baseUrl); ?>;
        var loteIds       = [];
        var vehiculosData = {};
        var selectedIds   = new Set();
        var tablaGlobal;

        $(document).ready(function () {
            tablaGlobal = $('#TablaVehiculos').DataTable({
                data: [],
                paging: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
                ordering: true,
                searching: true,
                info: true,
                autoWidth: false,
                order: [[1, 'asc']],
                columnDefs: [{ orderable: false, targets: [0, 6] }],
                language: {
                    decimal: ",",
                    thousands: ".",
                    processing: "Procesando...",
                    loadingRecords: "Cargando...",
                    zeroRecords: "No se encontraron resultados",
                    emptyTable: "No hay datos disponibles en la tabla",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 registros",
                    infoFiltered: "(filtrado de _MAX_ registros totales)",
                    search: "Buscar:",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    },
                    lengthMenu: "Mostrar _MENU_ registros"
                },
                drawCallback: function () {
                    // Restaurar estado de checkboxes al cambiar página o buscar
                    $('.chk-vehiculo').each(function () {
                        $(this).prop('checked', selectedIds.has(parseInt($(this).data('id'))));
                    });
                    actualizarChkTodos();
                }
            });

            cargarVehiculos(tablaGlobal);

            // Botón Agregar individual (data-id evita problemas de quoting con caracteres especiales)
            $(document).on('click', '.btn-agregar', function () {
                var id = parseInt($(this).data('id'));
                var v  = vehiculosData[id];
                if (v) agregarAlLote(v.id, v.placa, v.modelo, v.marca, v.anio);
            });

            // Checkbox individual
            $(document).on('change', '.chk-vehiculo', function () {
                var id = parseInt($(this).data('id'));
                if ($(this).is(':checked')) { selectedIds.add(id); } else { selectedIds.delete(id); }
                actualizarSeleccionados();
                actualizarChkTodos();
            });

            // Seleccionar / deseleccionar todos
            $('#chkTodos').on('change', function () {
                var marcar = $(this).is(':checked');
                Object.keys(vehiculosData).forEach(function (id) {
                    if (marcar) { selectedIds.add(parseInt(id)); } else { selectedIds.delete(parseInt(id)); }
                });
                $('.chk-vehiculo').prop('checked', marcar);
                actualizarSeleccionados();
            });
        });

        function cargarVehiculos(tabla) {
            $.ajax({
                url: 'AccionesCheckVehiculo.php',
                type: 'POST',
                data: { opcion: 'llenaTVehiculosAsignados', cookieNoEmpleado: getCookie('noEmpleado') },
                dataType: 'json',
                success: function (data) {
                    vehiculosData = {};
                    tabla.clear();
                    data.forEach(function (v) {
                        vehiculosData[v.id] = v;
                        var chk = '<div class="d-flex justify-content-center">'
                            + '<input type="checkbox" class="chk-vehiculo" data-id="' + v.id + '">'
                            + '</div>';
                        var acciones = '<div class="d-flex justify-content-center">'
                            + '<button class="btn btn-outline-success btn-sm btn-agregar" data-id="' + v.id + '" title="Agregar al lote">'
                            + '<i class="fas fa-plus me-1"></i> Agregar</button></div>';
                        tabla.row.add([
                            chk,
                            escapeHtml(v.placa),
                            escapeHtml(v.modelo),
                            escapeHtml(v.marca),
                            escapeHtml(v.color),
                            escapeHtml(v.anio),
                            acciones
                        ]);
                    });
                    tabla.draw();
                },
                error: function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar la lista de vehículos.', confirmButtonText: 'Aceptar' });
                }
            });
        }

        // Agregar al lote
        function agregarAlLote(id, placa, modelo, marca, anio) {
            if (loteIds.indexOf(id) !== -1) {
                Swal.fire({ icon: 'info', title: 'Ya en el lote', text: placa + ' ya está agregado.', timer: 1800, showConfirmButton: false });
                return;
            }

            var url     = baseUrl + '/qr_vehiculo.php?v=' + id;
            var qrDivId = 'qrLote' + id;

            var html = `
                <div class="sticker-item" id="stickerLote${id}">
                    <div class="sticker-body">
                        <div class="sticker-qr">
                            <div id="${qrDivId}"></div>
                            <img src="img/MESS_07_CuboMess_1.png" class="logo-qr" alt="MESS">
                        </div>
                        <div class="sticker-info">
                            <div class="sticker-nombre">${escapeHtml(marca + ' ' + modelo)}</div>
                            <div class="sticker-anio">${escapeHtml(String(anio))}</div>
                            <div class="sticker-placa-footer">${escapeHtml(placa)}</div>
                            <hr class="sticker-sep">
                            <div class="sticker-label">Control Vehicular</div>
                            <div class="sticker-mess">MESS</div>
                        </div>
                    </div>
                    <div class="sticker-footer">
                        <span class="sticker-soporte">Soporte Sistema: sebastian.gutierrez@mess.com.mx</span>
                    </div>
                    <button class="btn btn-outline-danger btn-sm btn-quitar-sticker w-100 no-print"
                        onclick="quitarDelLote(${id})">
                        <i class="fas fa-times me-1"></i> Quitar
                    </button>
                </div>`;

            $('#loteGrid').append(html);
            $('#loteContainer').show();

            new QRCode(document.getElementById(qrDivId), {
                text: url, width: 120, height: 120,
                colorDark: '#050D9E', colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });

            loteIds.push(id);
            actualizarContador();
            $('html, body').animate({ scrollTop: $('#loteContainer').offset().top - 20 }, 400);
        }

        function quitarDelLote(id) {
            $('#stickerLote' + id).remove();
            loteIds = loteIds.filter(function (i) { return i !== id; });
            actualizarContador();
            if (loteIds.length === 0) $('#loteContainer').hide();
        }

        function limpiarLote() {
            $('#loteGrid').empty();
            loteIds = [];
            actualizarContador();
            $('#loteContainer').hide();
        }

        function imprimirLote() {
            document.body.classList.add('print-lote');
            window.print();
            document.body.classList.remove('print-lote');
        }

        function actualizarContador() {
            var n = loteIds.length;
            $('#loteContador').text(n);
            if (n > 0) { $('#botonesLote').css('display', 'flex'); } else { $('#botonesLote').hide(); }
        }

        function actualizarSeleccionados() {
            var n = selectedIds.size;
            $('#cntSeleccionados').text(n);
            $('#btnAgregarSeleccionados').toggle(n > 0);
        }

        function actualizarChkTodos() {
            var total = Object.keys(vehiculosData).length;
            var sel   = selectedIds.size;
            var chk   = document.getElementById('chkTodos');
            if (!chk) return;
            chk.indeterminate = sel > 0 && sel < total;
            chk.checked       = total > 0 && sel === total;
        }

        function agregarSeleccionados() {
            selectedIds.forEach(function (id) {
                var v = vehiculosData[id];
                if (v) agregarAlLote(v.id, v.placa, v.modelo, v.marca, v.anio);
            });
            selectedIds.clear();
            $('.chk-vehiculo').prop('checked', false);
            actualizarChkTodos();
            actualizarSeleccionados();
        }

        function escapeHtml(str) {
            if (str == null) return '';
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function getCookie(name) {
            const cookies = new URLSearchParams(document.cookie.replace(/; /g, '&'));
            return cookies.get(name) || undefined;
        }
    </script>
</body>
</html>
