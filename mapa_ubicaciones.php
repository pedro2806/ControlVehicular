<?php
include 'conn.php';
if (empty($_COOKIE['noEmpleado'])) {
    echo '<script>window.location.assign("index")</script>';
    exit;
}

// Muestra ubicaciones de empleados: solo con el acceso especial verMapaGPS (retro
// 26/10/2026: 19, 183, 191, 276 y 523). Se valida antes de pintar nada; los datos los
// vuelve a validar acciones_mapa.php en cada petición.
$stmtGps = $conn->prepare(
    "SELECT 1 FROM mess_rrhh.accesos_especiales
     WHERE noEmpleado = ? AND sistema = 'ctrlVehicular' AND opcion = 'verMapaGPS' AND estatus = 1 LIMIT 1"
);
$noEmpGps = intval($_COOKIE['noEmpleado']);
$stmtGps->bind_param("i", $noEmpGps);
$stmtGps->execute();
$puedeVerMapa = (bool) $stmtGps->get_result()->fetch_assoc();
$stmtGps->close();
if (!$puedeVerMapa) {
    echo '<script>window.location.assign("inicio")</script>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Control Vehicular - Mapa de ubicaciones</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">

    <!-- MESS Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/mess-ds.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="css/lib/responsive.dataTables.min.css">
    <style>
        /* Alto relativo a la pantalla: en celular no se come toda la vista y en escritorio
           deja ver el inicio de la tabla de abajo. */
        #mapa { height: 60vh; min-height: 360px; width: 100%; border-radius: 0 0 .5rem .5rem; }
        .mu-mapa-caja { position: relative; }
        /* Aviso sobre el mapa (sin resultados / error / cargando) */
        .mu-aviso {
            position: absolute; inset: 0; z-index: 500; display: none;
            align-items: center; justify-content: center; text-align: center; padding: 1rem;
            background: color-mix(in srgb, var(--card-bg) 75%, transparent);
            color: var(--text); font-weight: 700;
        }
        .mu-aviso.es-visible { display: flex; }

        /* Marcador: un pin con el color de marca, sin las letras A–Z del mapa anterior
           (pasada la Z salían símbolos). */
        .mu-pin { background: transparent; border: 0; }
        .mu-pin i { font-size: 28px; color: var(--accent); filter: drop-shadow(0 1px 1px rgba(0, 0, 0, .45)); }
        .mu-pin.es-activo i { color: var(--danger); font-size: 34px; }

        /* Popup con los colores del tema (Leaflet lo trae siempre blanco) */
        #mapa .leaflet-popup-content-wrapper, #mapa .leaflet-popup-tip { background: var(--card-bg); color: var(--text); }
        .mu-popup { min-width: 210px; font-family: 'Nunito', sans-serif; }
        .mu-popup__fecha { font-weight: 800; margin-bottom: .35rem; }
        .mu-popup dl { display: grid; grid-template-columns: auto 1fr; gap: .15rem .6rem; margin: 0; font-size: .82rem; }
        .mu-popup dt { font-weight: 700; color: var(--text-muted); }
        .mu-popup dd { margin: 0; }

        #tablaDetalle tbody tr { cursor: pointer; }
        #tablaDetalle tbody tr.es-activo > * { box-shadow: none; background: var(--accent-soft); }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <?php include 'menu.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'encabezado.php'; ?>
            <div class="container-fluid">

                <div class="mb-3">
                    <h1 class="h3 mb-0 text-gray-800">Mapa de ubicaciones</h1>
                    <div class="small text-muted">Check-ins con ubicación según los filtros. Toca un marcador o una fila para ver el detalle.</div>
                </div>

                <!-- Filtros. Ya no está "Mostrar ubicaciones" (con/sin vehículo): el mapa
                     trabaja solo con check-ins, que siempre traen vehículo. -->
                <div class="card shadow mb-3">
                    <div class="card-body py-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label for="fUsuario" class="form-label mb-1 small fw-semibold">Usuario</label>
                                <select id="fUsuario" class="form-select form-select-sm"><option value="0">Todos</option></select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="fVehiculo" class="form-label mb-1 small fw-semibold">Vehículo</label>
                                <select id="fVehiculo" class="form-select form-select-sm"><option value="0">Todos</option></select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label for="fDesde" class="form-label mb-1 small fw-semibold">Desde</label>
                                <input type="date" id="fDesde" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-2">
                                <label for="fHasta" class="form-label mb-1 small fw-semibold">Hasta</label>
                                <input type="date" id="fHasta" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-md-2 d-grid">
                                <button id="btnBuscar" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i> Buscar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-3">
                    <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between gap-2">
                        <h6 class="m-0 fw-bold text-dark"><i class="fas fa-map-location-dot me-1"></i> Mapa</h6>
                        <span class="badge bg-secondary" id="badgeMapa"></span>
                    </div>
                    <div class="mu-mapa-caja">
                        <div id="mapa"></div>
                        <div class="mu-aviso" id="avisoMapa"></div>
                    </div>
                </div>

                <!-- Detalle con Excel. Lo ven solo quienes tienen verMapaGPS, igual que el
                     mapa: la página entera está restringida. -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <h6 class="m-0 fw-bold text-dark"><i class="fas fa-table-list me-1"></i> Detalle <span class="badge bg-secondary ms-1" id="badgeDetalle"></span></h6>
                        <button id="btnExcel" class="btn btn-outline-success btn-sm" disabled>
                            <i class="fas fa-file-excel me-1"></i> Descargar Excel
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="small text-muted mb-2" id="avisoSinCoordenadas" style="display:none;"></div>
                        <table id="tablaDetalle" class="table table-striped table-bordered table-sm w-100">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Placa</th>
                                    <th>Km</th>
                                    <th>Última carga de gasolina</th>
                                    <th>OT / OV</th>
                                    <th>Ubicación</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto"><span>&copy; MESS <?php echo date("Y"); ?></span></div>
            </div>
        </footer>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="js/lib/dataTables.responsive.min.js"></script>
<!-- xlsx-js-style: SheetJS con estilos (mismo que historial_gasolina.php). Expone XLSX. -->
<script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>

<script>
var mapa, capa, tabla;
var filas = [];          // ubicaciones del último resultado
var marcadores = {};     // id_actividad -> marcador
var marcadorActivo = null;
var rango = { desde: '', hasta: '' };

var iconoPin = L.divIcon({ className: 'mu-pin', html: '<i class="fas fa-location-dot"></i>',
                           iconSize: [28, 28], iconAnchor: [14, 28], popupAnchor: [0, -26] });
var iconoPinActivo = L.divIcon({ className: 'mu-pin es-activo', html: '<i class="fas fa-location-dot"></i>',
                                 iconSize: [34, 34], iconAnchor: [17, 34], popupAnchor: [0, -32] });

function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

/** 'YYYY-MM-DD HH:MM:SS' -> 'DD/MM/YYYY HH:MM'. Sin la hora 00:00 de las cargas viejas. */
function fmtFecha(f) {
    if (!f) return '';
    var p = String(f).split(/[- :]/);
    if (p.length < 3) return String(f);
    var conHora = p[3] !== undefined && !(p[3] === '00' && (p[4] || '00') === '00' && (p[5] || '00') === '00');
    return p[2] + '/' + p[1] + '/' + p[0] + (conHora ? ' ' + p[3] + ':' + (p[4] || '00') : '');
}

function fmtMoneda(n) {
    return '$' + (Number(n) || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function vehiculoTexto(f) {
    return [f.marca, f.modelo].filter(Boolean).join(' ').trim();
}

/** "Gasolina" del popup y la tabla: la última carga del vehículo antes del check-in. */
function cargaTexto(c) {
    if (!c) return 'Sin cargas registradas';
    return 'Última carga ' + fmtFecha(c.fecha) + ' · pagó ' + fmtMoneda(c.pagos) + ' · saldo ' + fmtMoneda(c.saldo);
}

function urlMaps(f) {
    return 'https://www.google.com/maps?q=' + encodeURIComponent(f.lat + ',' + f.lng);
}

function popupHtml(f) {
    var veh = vehiculoTexto(f);
    return '<div class="mu-popup">'
        + '<div class="mu-popup__fecha">' + esc(fmtFecha(f.fecha)) + '</div>'
        + '<dl>'
        +   '<dt>Usuario</dt><dd>' + esc(f.usuario) + '</dd>'
        +   '<dt>Placa</dt><dd>' + esc(f.placa) + (veh ? ' <span class="text-muted">· ' + esc(veh) + '</span>' : '') + '</dd>'
        +   '<dt>Km</dt><dd>' + (f.km ? Number(f.km).toLocaleString('es-MX') + ' km' : '—') + '</dd>'
        +   '<dt>Gasolina</dt><dd>' + esc(cargaTexto(f.carga)) + '</dd>'
        + '</dl></div>';
}

function avisoMapa(texto) {
    $('#avisoMapa').text(texto || '').toggleClass('es-visible', !!texto);
}

$(document).ready(function () {
    // Querétaro como vista inicial; en cuanto llegan datos se ajusta a los puntos.
    mapa = L.map('mapa').setView([20.59, -100.39], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap'
    }).addTo(mapa);
    capa = L.markerClusterGroup();
    mapa.addLayer(capa);

    // El ancho cambia al abrir o cerrar el menú lateral; Leaflet no se entera solo y deja
    // franjas grises.
    if (window.ResizeObserver) new ResizeObserver(function () { mapa.invalidateSize(); }).observe(document.getElementById('mapa'));

    tabla = $('#tablaDetalle').DataTable({
        data: [],
        responsive: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, 'desc']],
        autoWidth: false,
        rowId: function (f) { return 'ub-' + f.id; },
        columns: [
            // Fecha cruda para ordenar; formateada para mostrar.
            { data: 'fecha', render: function (d, t) { return (t === 'sort' || t === 'type') ? d : esc(fmtFecha(d)); } },
            { data: 'usuario', render: function (d) { return esc(d); } },
            { data: 'placa', render: function (d, t, f) {
                var veh = vehiculoTexto(f);
                return esc(d) + (veh && t === 'display' ? '<div class="small text-muted">' + esc(veh) + '</div>' : '');
            } },
            { data: 'km', className: 'text-end text-nowrap',
              render: function (d, t) { return t === 'display' ? (d ? Number(d).toLocaleString('es-MX') + ' km' : '—') : d; } },
            { data: 'carga', orderable: false, render: function (c) {
                if (!c) return '<span class="text-muted">Sin cargas registradas</span>';
                return esc(fmtFecha(c.fecha)) + '<div class="small text-muted">pagó ' + fmtMoneda(c.pagos) + ' · saldo ' + fmtMoneda(c.saldo) + '</div>';
            } },
            { data: 'ot', defaultContent: '', render: function (d) { return d ? esc(d) : '<span class="text-muted">—</span>'; } },
            { data: null, orderable: false, className: 'text-center', render: function (f) {
                // stopPropagation va en el handler de la fila: el enlace abre Maps sin
                // mover también el mapa de la página.
                return '<a href="' + esc(urlMaps(f)) + '" target="_blank" rel="noopener" class="mu-maps" title="Abrir en Google Maps">'
                     + '<i class="fas fa-up-right-from-square"></i></a>';
            } }
        ],
        language: {
            lengthMenu: 'Mostrar _MENU_ registros', zeroRecords: 'Sin resultados',
            emptyTable: 'Sin check-ins con ubicación en este rango',
            info: 'Mostrando _START_ a _END_ de _TOTAL_', infoEmpty: 'Sin registros',
            infoFiltered: '(filtrado de _MAX_)', search: 'Buscar:',
            paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
        }
    });

    // Fila -> el mapa va al marcador y abre su detalle. Las filas hijas de Responsive
    // (celular) no tienen datos propios y se ignoran.
    $('#tablaDetalle tbody').on('click', 'tr', function (e) {
        if ($(e.target).closest('a, .dtr-control').length) return;
        var f = tabla.row(this).data();
        if (f) enfocar(f.id, true);
    });

    $('#btnBuscar').on('click', buscar);
    $('#btnExcel').on('click', descargarExcel);

    cargarFiltros();
    buscar();
});

function cargarFiltros() {
    $.ajax({
        url: 'acciones_mapa.php', method: 'POST', dataType: 'json', data: { accion: 'filtros' },
        success: function (resp) {
            if (!resp || resp.status !== 'success') return;
            if (Array.isArray(resp.usuarios)) {
                resp.usuarios.forEach(function (u) {
                    $('#fUsuario').append($('<option>').val(u.id_usuario).text(u.nombre));
                });
                hacerSelectBuscable('fUsuario', 'Buscar usuario...');
            }
            if (Array.isArray(resp.vehiculos)) {
                resp.vehiculos.forEach(function (v) {
                    $('#fVehiculo').append($('<option>').val(v.id_vehiculo).text(etiquetaVehiculo(v)));
                });
                hacerSelectBuscable('fVehiculo', 'Buscar placa, modelo o usuario...');
            }
        }
    });
}

function buscar() {
    avisoMapa('Cargando ubicaciones…');
    $('#btnBuscar').prop('disabled', true);
    $.ajax({
        url: 'acciones_mapa.php', method: 'POST', dataType: 'json',
        data: {
            accion: 'ubicaciones',
            desde: $('#fDesde').val(), hasta: $('#fHasta').val(),
            id_usuario: $('#fUsuario').val(), id_vehiculo: $('#fVehiculo').val()
        },
        success: function (resp) {
            if (!resp || resp.status !== 'success') {
                avisoMapa((resp && resp.message) || 'No se pudieron cargar las ubicaciones.');
                return;
            }
            // Array.isArray antes de iterar: ante un error el endpoint manda un objeto.
            filas = Array.isArray(resp.ubicaciones) ? resp.ubicaciones : [];
            rango = { desde: resp.desde, hasta: resp.hasta };
            $('#fDesde').val(resp.desde);
            $('#fHasta').val(resp.hasta);
            var sinCoord = parseInt(resp.sin_coordenadas, 10) || 0;
            $('#avisoSinCoordenadas').toggle(sinCoord > 0)
                .text(sinCoord + (sinCoord === 1 ? ' check-in no tiene' : ' check-ins no tienen') + ' una ubicación válida y no aparecen.');
            pintar();
        },
        error: function (xhr) {
            // 403 = sin el acceso verMapaGPS (por ejemplo, si se lo quitaron con la página abierta).
            avisoMapa(xhr.status === 403 ? 'No tienes acceso al mapa de ubicaciones.' : 'No se pudieron cargar las ubicaciones.');
        },
        complete: function () { $('#btnBuscar').prop('disabled', false); }
    });
}

function pintar() {
    capa.clearLayers();
    marcadores = {};
    marcadorActivo = null;

    filas.forEach(function (f) {
        var m = L.marker([f.lat, f.lng], { icon: iconoPin });
        m.bindPopup(popupHtml(f));
        m.on('click', function () { enfocar(f.id, false); });
        marcadores[f.id] = m;
        capa.addLayer(m);
    });

    if (filas.length) {
        avisoMapa('');
        mapa.fitBounds(capa.getBounds(), { padding: [30, 30], maxZoom: 15 });
    } else {
        avisoMapa('Sin check-ins con ubicación en este rango.');
    }

    tabla.clear().rows.add(filas).draw();
    var txt = filas.length + (filas.length === 1 ? ' registro' : ' registros');
    $('#badgeMapa, #badgeDetalle').text(txt);
    $('#btnExcel').prop('disabled', !filas.length);
}

/**
 * Lleva el mapa a un check-in y abre su detalle. desdeTabla: además sube la vista al
 * mapa, porque la tabla queda debajo y el usuario no vería el cambio.
 */
function enfocar(id, desdeTabla) {
    var m = marcadores[id];
    if (!m) return;
    if (marcadorActivo && marcadorActivo !== m) marcadorActivo.setIcon(iconoPin);
    marcadorActivo = m;
    m.setIcon(iconoPinActivo);

    $('#tablaDetalle tbody tr.es-activo').removeClass('es-activo');
    $('#ub-' + id).addClass('es-activo');

    if (desdeTabla) document.getElementById('mapa').scrollIntoView({ behavior: 'smooth', block: 'center' });
    // zoomToShowLayer abre el cluster que lo contiene antes de mostrar el popup.
    capa.zoomToShowLayer(m, function () { m.openPopup(); });
}

/**
 * Excel del detalle: todas las filas que pasan el buscador de la tabla (no solo la página
 * visible), con coordenadas y enlace a Google Maps.
 */
function descargarExcel() {
    var lista = tabla.rows({ search: 'applied' }).data().toArray();
    if (!lista.length) return;

    var enc = ['Fecha', 'Hora', 'Usuario', 'Placa', 'Vehículo', 'Km', 'Última carga', 'Pagó', 'Saldo tras la carga', 'OT / OV', 'Latitud', 'Longitud', 'Google Maps'];
    var aoa = [enc].concat(lista.map(function (f) {
        var p = String(f.fecha).split(' ');
        var d = p[0].split('-');
        return [
            d[2] + '/' + d[1] + '/' + d[0], (p[1] || '').slice(0, 5), f.usuario, f.placa, vehiculoTexto(f),
            f.km || 0,
            f.carga ? fmtFecha(f.carga.fecha) : 'Sin cargas',
            f.carga ? f.carga.pagos : '', f.carga ? f.carga.saldo : '',
            f.ot || '', f.lat, f.lng, urlMaps(f)
        ];
    }));

    var ws = XLSX.utils.aoa_to_sheet(aoa);
    var estiloEnc = { font: { bold: true, color: { rgb: 'FFFFFF' } }, fill: { fgColor: { rgb: '0A1C61' } },
                      alignment: { vertical: 'center', wrapText: true } };
    for (var c = 0; c < enc.length; c++) ws[XLSX.utils.encode_cell({ r: 0, c: c })].s = estiloEnc;
    for (var r = 1; r < aoa.length; r++) {
        var km = ws[XLSX.utils.encode_cell({ r: r, c: 5 })];  if (km) km.z = '#,##0';
        [7, 8].forEach(function (col) { var x = ws[XLSX.utils.encode_cell({ r: r, c: col })]; if (x && x.t === 'n') x.z = '"$"#,##0.00'; });
        var link = ws[XLSX.utils.encode_cell({ r: r, c: 12 })];
        if (link) { link.l = { Target: link.v }; link.v = 'Ver en mapa'; link.s = { font: { color: { rgb: '1C83F1' }, underline: true } }; }
    }
    ws['!cols'] = [12, 7, 28, 10, 18, 10, 18, 11, 14, 18, 11, 11, 13].map(function (w) { return { wch: w }; });
    ws['!autofilter'] = { ref: XLSX.utils.encode_range({ s: { r: 0, c: 0 }, e: { r: aoa.length - 1, c: enc.length - 1 } }) };

    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Ubicaciones');
    XLSX.writeFile(wb, 'Ubicaciones_' + rango.desde + '_a_' + rango.hasta + '.xlsx');
}
</script>
</body>
</html>
