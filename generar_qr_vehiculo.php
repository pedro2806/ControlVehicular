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
// URL base que se codifica en el QR impreso. Es CONSTANTE a propósito: la etiqueta
// es física y permanente, así que no puede depender de desde dónde se generó.
// Derivándola de HTTP_HOST, generar el lote desde WAMP imprimía stickers con
// http://localhost/... de forma irreversible.
//
// Capacidad del QR: typeNumber 6 + EC 'Q' = 74 bytes; con el id del vehículo esta
// URL usa ~62. Si la constante crece más allá de eso, la librería lanza excepción y
// el sticker sale vacío: hay que subir typeNumber en generarQrUnico() y ajustar el
// ancho a un múltiplo de sus módulos (17 + 4*typeNumber).
$QR_BASE_URL = 'https://messbook.com.mx/ControlVehicular';

// Solo para avisar en pantalla que se está previsualizando fuera de producción.
$esProduccion = stripos($_SERVER['HTTP_HOST'] ?? '', 'messbook.com.mx') !== false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Control Vehicular - Generar QR</title>

    <!-- MESS Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/mess-ds.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <link href="css/app.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'menu.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-3 text-black-800 no-print">Generar QR por Vehículo</h1>

<?php if (!$esProduccion): ?>
                    <div class="alert alert-warning d-flex align-items-center gap-2 no-print" role="alert">
                        <i class="fas fa-triangle-exclamation"></i>
                        <div>
                            <strong>Vista local.</strong> Los QR de estas etiquetas apuntan a
                            <strong>PRODUCCIÓN</strong> (<?= htmlspecialchars($QR_BASE_URL) ?>), no a este servidor.
                        </div>
                    </div>
<?php endif; ?>

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

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        var baseUrl       = <?php echo json_encode($QR_BASE_URL); ?>;
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
                    Array.isArray(data) && data.forEach(function (v) {
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
        // Hace únicos los IDs internos del SVG del QR (clip-path-dot-color, etc.).
        // qr-code-styling los emite SIN sufijo de instancia: 'clip-path-dot-color' y
        // 'clip-path-background-color' son literales, y los de las esquinas se indexan
        // por posición ('...-0-0'), no por QR. Con varios QR en la misma página los
        // url(#id) colisionan —SVG resuelve al primer id del documento— y TODAS las
        // etiquetas acaban pintando el QR de la primera. Pasó en producción: se
        // imprimió un lote entero donde todas escaneaban al mismo vehículo.
        // Se les añade el id del contenedor como sufijo.
        var ATRIBUTOS_CON_REF = ['clip-path', 'mask', 'fill', 'stroke'];

        function hacerIdsUnicos(cont, sufijo) {
            var svg = cont.querySelector('svg');
            if (!svg) return false;
            var conId = svg.querySelectorAll('[id]');
            if (!conId.length) return false;
            var nodos = svg.querySelectorAll('*');
            conId.forEach(function (el) {
                var viejo = el.id, nuevo = viejo + '-' + sufijo;
                el.id = nuevo;
                // La librería escribe las referencias como url('#id') CON comillas
                // simples, así que no sirve comparar contra la cadena url(#id) pelada.
                // Se contemplan las tres formas: url(#id), url('#id') y url("#id").
                // El \) final es lo que evita que #clip-path-corners-dot-color haga
                // match parcial dentro de #clip-path-corners-dot-color-0-0.
                var ref = new RegExp(
                    'url\\((["\']?)#' + viejo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\1\\)',
                    'g'
                );
                nodos.forEach(function (n) {
                    ATRIBUTOS_CON_REF.forEach(function (a) {
                        var val = n.getAttribute(a);
                        if (val && val.indexOf('#' + viejo) !== -1) {
                            n.setAttribute(a, val.replace(ref, 'url($1#' + nuevo + '$1)'));
                        }
                    });
                });
            });
            return true;
        }

        function generarQrUnico(qrDivId, url) {
            var cont = document.getElementById(qrDivId);
            if (!cont) return;
            new QRCodeStyling({
                width: 123,
                height: 123,
                margin: 0,
                type: 'svg',
                data: url,
                // typeNumber 6 = 41 módulos -> 123/41 = 3px exactos por módulo.
                // Sin fijarlo, la versión la elige la librería según el largo de la URL:
                // los ids de 1 dígito daban v5 (37 módulos) -> floor(123/37)=3 -> QR de
                // 111px, y el fondo blanco de 123x123 asomaba alrededor.
                // Capacidad v6 con EC 'Q' = 74 bytes (la URL usa ~62). Si baseUrl crece
                // más allá de eso hay que subir typeNumber Y ajustar el ancho a múltiplo
                // de sus módulos (17 + 4*typeNumber).
                qrOptions: { typeNumber: 6, errorCorrectionLevel: 'Q' },
                dotsOptions:          { color: '#074480', type: 'square' },
                backgroundOptions:    { color: '#ffffff' },
                cornersSquareOptions: { color: '#074480', type: 'square' },
                cornersDotOptions:    { color: '#074480', type: 'square' },
                image: 'img/MESS_07_CuboMess_1.png',
                imageOptions: { crossOrigin: 'anonymous', margin: 0, imageSize: 0.3 }
            }).append(cont);
            // qr-code-styling dibuja de forma asíncrona; en cuanto el SVG esté listo
            // lo preparamos y desconectamos el observer.
            var obs = new MutationObserver(function () {
                if (prepararSvg(cont, qrDivId)) obs.disconnect();
            });
            obs.observe(cont, { childList: true, subtree: true });
            setTimeout(function () { if (prepararSvg(cont, qrDivId)) obs.disconnect(); }, 600);
        }

        // La librería crea el <svg> con width/height="123" y SIN viewBox. Sin viewBox
        // el dibujo tiene tamaño fijo en unidades de usuario: darle un ancho por CSS
        // encoge la ventana pero NO el contenido, así que el QR sale RECORTADO en vez
        // de escalado. Cambiando width/height por un viewBox equivalente, el SVG pasa
        // a escalar de verdad y obedece el % de .sticker-qr-ring en css/app.css.
        function hacerEscalable(svg) {
            if (svg.getAttribute('viewBox')) return;
            var w = parseFloat(svg.getAttribute('width')),
                h = parseFloat(svg.getAttribute('height'));
            if (!w || !h) return;
            svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
            svg.removeAttribute('width');
            svg.removeAttribute('height');
        }

        // Idempotente a propósito: la llaman el MutationObserver y el setTimeout de
        // respaldo, y sin la marca el sufijo se aplicaba dos veces
        // (clip-path-dot-color-qrLote5-qrLote5).
        function prepararSvg(cont, sufijo) {
            var svg = cont.querySelector('svg');
            if (!svg) return false;
            if (svg.dataset.qrListo === '1') return true;
            if (!hacerIdsUnicos(cont, sufijo)) return false;
            hacerEscalable(svg);
            svg.dataset.qrListo = '1';
            return true;
        }

        function agregarAlLote(id, placa, modelo, marca, anio, skipScroll) {
            if (loteIds.indexOf(id) !== -1) {
                Swal.fire({ icon: 'info', title: 'Ya en el lote', text: placa + ' ya está agregado.', timer: 1800, showConfirmButton: false });
                return;
            }

            var url     = baseUrl + '/qr_vehiculo.php?v=' + id;
            var qrDivId = 'qrLote' + id;

            var html = `
                <div class="sticker-item" id="stickerLote${id}">
                    <div class="sticker-body">
                        <div class="sticker-left">
                            <div class="sticker-qr-ring">
                                <div id="${qrDivId}"></div>
                            </div>
                        </div>
                        <div class="sticker-right">
                            <img src="img/QRide_grande.png" class="sticker-ride-img" alt="RIDE - Sistema de Control Vehicular">
                            <div class="sticker-infobox">
                                <div class="sticker-v-line1">${escapeHtml(modelo)}</div>
                                <div class="sticker-v-line2">${escapeHtml(marca)}</div>
                                <div class="sticker-v-line3">${escapeHtml(String(anio))}</div>
                                <div class="sticker-v-line4">${escapeHtml(placa)}</div>
                            </div>
                            <div class="sticker-logos-bar">
                                <img src="img/MESS_05_Imagotipo_1.png" class="sticker-logo-mess" alt="grupo mess">
                                <div class="sticker-logo-divider"></div>
                                <div class="sticker-b1">
                                    <div class="b1-box">B1</div>
                                    <div class="b1-desc">Business<br>Intelligence</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-outline-danger btn-sm btn-quitar-sticker w-100 no-print"
                        onclick="quitarDelLote(${id})">
                        <i class="fas fa-times me-1"></i> Quitar
                    </button>
                </div>`;

            $('#loteGrid').append(html);
            $('#loteContainer').show();

            // SIEMPRE vía generarQrUnico(). Instanciar QRCodeStyling aquí en línea se
            // salta hacerIdsUnicos() y todas las etiquetas del lote salen con el QR de
            // la primera. Ya se perdió una vez resolviendo un merge (67c4673) y llegó
            // así a producción.
            generarQrUnico(qrDivId, url);

            loteIds.push(id);
            actualizarContador();
            if (!skipScroll) $('html, body').animate({ scrollTop: $('#loteContainer').offset().top - 20 }, 400);
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
            var ids = Array.from(selectedIds);
            selectedIds.clear();
            $('.chk-vehiculo').prop('checked', false);
            actualizarChkTodos();
            actualizarSeleccionados();

            // Generación SECUENCIAL con respiro entre cada uno. qr-code-styling
            // dibuja de forma asíncrona (carga la imagen central antes de pintar
            // el fondo/módulos); si se disparan todos en el mismo tick los renders
            // se pisan y salen QR incompletos (el fondo blanco no cubre y el patrón
            // del círculo se cuela). Uno a la vez = cada render termina limpio.
            var i = 0;
            (function next() {
                if (i >= ids.length) {
                    if (ids.length) $('html, body').animate({ scrollTop: $('#loteContainer').offset().top - 20 }, 400);
                    return;
                }
                var v = vehiculosData[ids[i]];
                if (v) agregarAlLote(v.id, v.placa, v.modelo, v.marca, v.anio, true);
                i++;
                setTimeout(next, 70);
            })();
        }

        function escapeHtml(str) {
            if (str == null) return '';
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

    </script>
</body>
</html>
