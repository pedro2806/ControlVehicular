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
        /* Crece con la tarjeta: a su lado va el panel de uso, que puede quedar más alto. */
        #mapa { flex: 1 1 auto; min-height: max(360px, 60vh); width: 100%; border-radius: 0 0 .5rem .5rem; }
        .mu-mapa-caja { position: relative; flex: 1 1 auto; display: flex; flex-direction: column; }
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

        /* ===== Panel "Uso del vehículo" (retro 03/11/2026) ===== */
        /* Semáforo de % uso Mess: rojo < 50, amarillo 50–79, azul >= 80, gris sin odómetro. */
        .uv-sem-rojo     { --sem: var(--danger); }
        .uv-sem-amarillo { --sem: var(--warning); }
        .uv-sem-azul     { --sem: var(--accent); }
        .uv-sem-gris     { --sem: var(--mess-gris); }

        /* Dona segmentada: arco de --pct en acento, separadores del color de la tarjeta. */
        .uv-dona {
            --pct: 0; flex: none; position: relative; border-radius: 50%;
            width: 150px; aspect-ratio: 1;
            background:
                repeating-conic-gradient(var(--card-bg) 0deg 1.6deg, transparent 1.6deg 18deg),
                conic-gradient(var(--accent) calc(var(--pct) * 1%), color-mix(in srgb, var(--accent) 22%, var(--card-bg)) 0);
        }
        .uv-dona::after { content: ''; position: absolute; inset: 20%; border-radius: 50%; background: var(--card-bg); }
        .uv-dona__txt {
            position: absolute; inset: 0; z-index: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; text-align: center; line-height: 1.1;
        }
        .uv-dona__pct { font-size: 1.7rem; font-weight: 800; color: var(--accent); }
        .uv-dona__lbl { font-size: .72rem; color: var(--text-muted); }
        .uv-dona--mini { width: 48px; }
        .uv-dona--mini::after { inset: 21%; }
        .uv-dona--mini .uv-dona__pct { font-size: .58rem; color: var(--text); }
        .uv-dona.es-vacia .uv-dona__pct { color: var(--text-muted); }

        /* Tira de tarjetas con scroll horizontal */
        /* position: relative para que offsetLeft de cada tarjeta se mida contra la tira */
        .uv-tira { position: relative; display: flex; gap: .5rem; overflow-x: auto; padding-bottom: .4rem; margin-bottom: .75rem; }
        .uv-tarjeta {
            flex: 0 0 auto; display: flex; align-items: center; gap: .5rem; position: relative;
            min-width: 150px; padding: .4rem 1.4rem .4rem .45rem; text-align: left;
            background: var(--card-bg); color: var(--text);
            border: 2px solid var(--border); border-radius: .6rem; cursor: pointer;
        }
        .uv-tarjeta:hover { border-color: color-mix(in srgb, var(--accent) 50%, var(--border)); }
        .uv-tarjeta.es-activa { border-color: var(--accent); box-shadow: 0 0 0 2px var(--accent-soft); }
        .uv-tarjeta__txt { font-size: .72rem; line-height: 1.15; }
        .uv-tarjeta__txt b { display: block; font-size: .78rem; }
        .uv-tarjeta__punto {
            position: absolute; top: .4rem; right: .4rem; width: .7rem; height: .7rem; border-radius: 50%;
            background: var(--sem); box-shadow: 0 0 0 1px color-mix(in srgb, var(--text) 25%, transparent);
        }

        /* Ficha del vehículo */
        .uv-ficha { border: 2px solid var(--border); border-radius: .9rem; padding: 1rem; }
        .uv-cab { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1rem; margin-bottom: 1rem; }
        .uv-foto {
            flex: none; width: 120px; height: 80px; display: flex; align-items: center; justify-content: center;
            border-radius: .5rem; background: var(--card-soft); color: var(--text-muted); overflow: hidden;
        }
        .uv-foto img { width: 100%; height: 100%; object-fit: cover; }
        .uv-titulo { line-height: 1.15; }
        .uv-titulo__marca { font-size: 1.35rem; text-transform: uppercase; }
        .uv-titulo__marca small { font-size: .8rem; color: var(--text-muted); text-transform: none; }
        .uv-titulo__anio { font-size: 1.1rem; color: var(--text-muted); }
        .uv-titulo__placa { font-size: 1.45rem; font-weight: 800; letter-spacing: .03em; }
        .uv-meta { margin: 0; font-size: .85rem; }
        .uv-meta dt { font-weight: 400; color: var(--text-muted); }
        .uv-meta dd { font-weight: 800; margin-bottom: .2rem; }

        .uv-cuerpo { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 1rem; }
        .uv-km { margin: 0; font-size: .82rem; min-width: 140px; }
        .uv-km dt { font-weight: 400; color: var(--text-muted); }
        .uv-km dd { font-weight: 800; font-size: .95rem; margin-bottom: .45rem; }
        .uv-refs {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: .25rem; margin-top: 1rem;
            padding: .6rem 1rem; text-align: center;
            border-radius: .5rem; background: var(--accent); color: var(--card-bg);
        }
        .uv-refs div { font-size: .8rem; }
        .uv-refs b { display: block; font-size: 1.35rem; line-height: 1.1; }
        .uv-rend {
            margin-top: 1rem; padding: .5rem 1rem; text-align: center; border-radius: .5rem;
            background: var(--card-soft); border: 1px solid var(--border);
        }
        .uv-rend__lbl { font-size: .85rem; color: var(--text-muted); }
        .uv-rend__val { font-size: 1.2rem; font-weight: 800; }
        .uv-km dt[title] { cursor: help; }
        .uv-ayuda { font-size: .72rem; opacity: .7; }
        .uv-vacio { padding: 2rem 1rem; text-align: center; color: var(--text-muted); }
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

                <div class="row g-3 mb-3">
                    <div class="col-12 col-xl-7">
                        <div class="card shadow h-100 d-flex flex-column">
                            <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between gap-2">
                                <h6 class="m-0 fw-bold text-dark"><i class="fas fa-map-location-dot me-1"></i> Mapa</h6>
                                <span class="badge bg-secondary" id="badgeMapa"></span>
                            </div>
                            <div class="mu-mapa-caja">
                                <div id="mapa"></div>
                                <div class="mu-aviso" id="avisoMapa"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Uso del vehículo (retro 03/11/2026). Las cifras son del vehículo en el
                         rango Desde–Hasta, sin importar quién manejó; las calcula
                         acciones_mapa.php (accion=resumen). -->
                    <div class="col-12 col-xl-5">
                        <div class="card shadow h-100">
                            <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between gap-2">
                                <h6 class="m-0 fw-bold text-dark"><i class="fas fa-gauge-high me-1"></i> Uso del vehículo</h6>
                                <span class="small text-muted" id="uvRango"></span>
                            </div>
                            <div class="card-body">
                                <div class="uv-tira" id="uvTira"></div>
                                <div id="uvFicha"><div class="uv-vacio">Cargando…</div></div>
                            </div>
                        </div>
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
// Panel de uso: vehículo de la ficha, tarjetas de la tira (respuesta de accion=resumen) y
// un contador para descartar respuestas viejas cuando se piden dos seguidas.
var uv = { activo: 0, tarjetas: [], turno: 0, error: '' };

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
        if (f) { enfocar(f.id, true); seleccionarVehiculo(f.id_vehiculo); }
    });

    $('#uvTira').on('click', '.uv-tarjeta', function () {
        uv.activo = parseInt($(this).data('id'), 10) || 0;
        mostrarActivo();
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

            // El panel sigue a los filtros, y sus cifras dependen del rango: se recarga en
            // cada búsqueda.
            uv.activo = vehiculoParaPanel();
            cargarPanel();
        },
        error: function (xhr) {
            // 403 = sin el acceso verMapaGPS (por ejemplo, si se lo quitaron con la página abierta).
            avisoMapa(xhr.status === 403 ? 'No tienes acceso al mapa de ubicaciones.' : 'No se pudieron cargar las ubicaciones.');
            if (!uv.tarjetas.length) { uv.error = 'No se pudo cargar el uso del vehículo.'; pintarPanel(); }
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
        m.on('click', function () { enfocar(f.id, false); seleccionarVehiculo(f.id_vehiculo); });
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

/* ===== Panel "Uso del vehículo" (retro 03/11/2026) =====
 * Tira: todos los vehículos activos, primero los que se movieron en el rango (así los
 * ordena acciones_mapa.php). Ficha: el vehículo activo, que se elige con el filtro
 * Vehículo, un marcador, una fila de la tabla o una tarjeta. Todas las cifras ya vienen en
 * la respuesta: cambiar de vehículo no vuelve a consultar. */

var SIN_CHECKINS_USUARIO = -1;   // uv.activo cuando el usuario filtrado no tiene check-ins

/**
 * Vehículo de la ficha tras una búsqueda: el del filtro Vehículo; con un usuario en el
 * filtro, el que más usó en el rango (antes se quedaba el vehículo anterior y parecía que
 * el filtro no hacía nada); sin ninguno de los dos, 0 = la primera tarjeta, la de más km.
 */
function vehiculoParaPanel() {
    var veh = parseInt($('#fVehiculo').val(), 10) || 0;
    if (veh) return veh;
    if (!(parseInt($('#fUsuario').val(), 10) || 0)) return 0;
    if (!filas.length) return SIN_CHECKINS_USUARIO;

    // filas ya viene filtrada por el usuario. En empate gana el que llegó primero a esa
    // cuenta, y filas va de la más reciente a la más vieja.
    var cuenta = {}, mejor = 0;
    filas.forEach(function (f) {
        cuenta[f.id_vehiculo] = (cuenta[f.id_vehiculo] || 0) + 1;
        if (!mejor || cuenta[f.id_vehiculo] > cuenta[mejor]) mejor = f.id_vehiculo;
    });
    return mejor;
}

/**
 * Con un usuario en el filtro, los vehículos que usó en el rango van primero en la tira
 * (por cuántos check-ins hizo en cada uno); el resto sigue en el orden del servidor.
 */
function ordenarTira(lista) {
    if (!(parseInt($('#fUsuario').val(), 10) || 0)) return lista;
    var cuenta = {};
    filas.forEach(function (f) { cuenta[f.id_vehiculo] = (cuenta[f.id_vehiculo] || 0) + 1; });
    var suyos = lista.filter(function (t) { return cuenta[t.id_vehiculo]; })
                     .sort(function (a, b) { return cuenta[b.id_vehiculo] - cuenta[a.id_vehiculo]; });
    return suyos.concat(lista.filter(function (t) { return !cuenta[t.id_vehiculo]; }));
}

/** Marcador o fila de la tabla: su vehículo pasa a la ficha. */
function seleccionarVehiculo(id) {
    id = parseInt(id, 10) || 0;
    if (!id) return;
    uv.activo = id;
    mostrarActivo();
}

function cargarPanel() {
    var turno = ++uv.turno;
    $.ajax({
        url: 'acciones_mapa.php', method: 'POST', dataType: 'json',
        data: { accion: 'resumen', desde: rango.desde, hasta: rango.hasta },
        success: function (resp) {
            if (turno !== uv.turno) return;   // ya se pidió otra más nueva
            var ok = resp && resp.status === 'success';
            uv.error = ok ? '' : ((resp && resp.message) || 'No se pudo cargar el uso del vehículo.');
            // Array.isArray antes de iterar: ante un error el endpoint manda un objeto.
            uv.tarjetas = ordenarTira(ok && Array.isArray(resp.vehiculos) ? resp.vehiculos : []);
            pintarPanel();
        },
        error: function (xhr) {
            if (turno !== uv.turno) return;
            uv.error = xhr.status === 403 ? 'No tienes acceso al mapa de ubicaciones.' : 'No se pudo cargar el uso del vehículo.';
            uv.tarjetas = [];
            pintarPanel();
        }
    });
}

/** Semáforo de % uso Mess: rojo < 50, amarillo 50–79, azul >= 80, gris sin odómetro. */
function claseSemaforo(pct) {
    if (pct === null || pct === undefined) return 'uv-sem-gris';
    return pct < 50 ? 'uv-sem-rojo' : (pct < 80 ? 'uv-sem-amarillo' : 'uv-sem-azul');
}

function textoSemaforo(pct) {
    if (pct === null || pct === undefined) return 'Sin km de odómetro en el rango';
    return pct + '% de uso Mess';
}

function donaHtml(pct, mini) {
    var vacia = pct === null || pct === undefined;
    return '<div class="uv-dona' + (mini ? ' uv-dona--mini' : '') + (vacia ? ' es-vacia' : '') + '" style="--pct:' + (vacia ? 0 : Number(pct)) + '">'
         + '<div class="uv-dona__txt"><span class="uv-dona__pct">' + (vacia ? '—' : Number(pct) + '%') + '</span>'
         + (mini ? '' : '<span class="uv-dona__lbl">' + (vacia ? 'sin odómetro' : '% uso Mess') + '</span>') + '</div></div>';
}

function fmtKm(n) {
    return (Number(n) || 0).toLocaleString('es-MX', { maximumFractionDigits: 0 }) + ' km';
}

var ICONO_AUTO = '<i class="fas fa-car-side fa-2x"></i>';

// Regla de KM Mess del 04/11/2026 (sqlKmMessCheckin en calcular_ruta.php), para el title.
var AYUDA_KM_MESS = 'Lo que avanzó el odómetro de un check-in al siguiente, cuando el siguiente es del '
    + 'mismo día o del día siguiente.';
var AYUDA_KM_PERSONALES = 'Lo que avanzó el odómetro entre dos check-ins con al menos un día completo sin '
    + 'check-ins en medio (fines de semana, días sin usar QRide).';

/** Con datos nuevos: redibuja la tira completa y luego la ficha. */
function pintarPanel() {
    $('#uvRango').text(rango.desde ? fmtFecha(rango.desde) + ' – ' + fmtFecha(rango.hasta) : '');

    var lista = uv.tarjetas;
    $('#uvTira').toggle(lista.length > 0).html(lista.map(function (t) {
        return '<button type="button" class="uv-tarjeta ' + claseSemaforo(t.pct_uso) + '"'
             + ' data-id="' + t.id_vehiculo + '" title="' + esc(vehiculoTexto(t) + ' · ' + textoSemaforo(t.pct_uso)) + '">'
             + donaHtml(t.pct_uso, true)
             + '<span class="uv-tarjeta__txt">' + esc(t.marca || '') + '<br>' + esc(t.anio || '') + '<b>' + esc(t.placa) + '</b></span>'
             + '<span class="uv-tarjeta__punto"></span>'
             + '</button>';
    }).join(''));
    mostrarActivo();
}

/**
 * Ficha del vehículo activo, su tarjeta marcada y la tira desplazada hasta ella (con ~100
 * tarjetas, la de un marcador o una fila suele quedar fuera de la vista). No redibuja la
 * tira: así no pierde el desplazamiento al tocar una tarjeta.
 */
function mostrarActivo() {
    var lista = uv.tarjetas;
    var v = lista.find(function (x) { return x.id_vehiculo === uv.activo; });
    // Sin vehículo elegido (o uno que ya no está activo): la primera tarjeta. Salvo si el
    // usuario filtrado no tiene check-ins: mostrar otro vehículo haría creer que es el suyo.
    if (!v && lista.length && uv.activo !== SIN_CHECKINS_USUARIO) { v = lista[0]; uv.activo = v.id_vehiculo; }

    var tira = document.getElementById('uvTira');
    $(tira).find('.uv-tarjeta.es-activa').removeClass('es-activa');
    var tarjeta = v ? tira.querySelector('.uv-tarjeta[data-id="' + v.id_vehiculo + '"]') : null;
    if (tarjeta) {
        tarjeta.classList.add('es-activa');
        // Solo horizontal: scrollIntoView movería también la página.
        var izq = tarjeta.offsetLeft, der = izq + tarjeta.offsetWidth;
        if (izq < tira.scrollLeft || der > tira.scrollLeft + tira.clientWidth) {
            tira.scrollTo({ left: Math.max(0, izq - 8), behavior: 'smooth' });
        }
    }

    if (!v) {
        var vacio = uv.error || (uv.activo === SIN_CHECKINS_USUARIO
            ? 'Este usuario no tiene check-ins en el rango. Elige una tarjeta para ver otro vehículo.'
            : 'Elige un vehículo en el filtro, en el mapa o en la tabla.');
        $('#uvFicha').html('<div class="uv-vacio">' + esc(vacio) + '</div>');
        return;
    }

    $('#uvFicha').html(
        '<div class="uv-ficha">'
        + '<div class="uv-cab">'
        +   '<div class="uv-foto">' + (v.foto ? '<img alt="" src="' + esc(v.foto) + '">' : ICONO_AUTO) + '</div>'
        +   '<div class="uv-titulo">'
        +     '<div class="uv-titulo__marca">' + esc(v.marca || '') + (v.modelo ? ' <small>' + esc(v.modelo) + '</small>' : '') + '</div>'
        +     (v.anio ? '<div class="uv-titulo__anio">' + esc(v.anio) + '</div>' : '')
        +     '<div class="uv-titulo__placa">' + esc(v.placa) + '</div>'
        +   '</div>'
        +   '<dl class="uv-meta">'
        +     '<dt>Asignado</dt><dd>' + esc(v.asignado || 'Sin asignar') + '</dd>'
        +     '<dt>Región</dt><dd>' + esc(v.region || '—') + '</dd>'
        +   '</dl>'
        + '</div>'
        + '<div class="uv-cuerpo">'
        +   '<div class="text-center" title="KM Mess ÷ km del odómetro en el rango">' + donaHtml(v.pct_uso, false)
        +     '<div class="small text-muted mt-1">de ' + fmtKm(v.km_odometro) + ' recorridos</div></div>'
        +   '<dl class="uv-km">'
        +     '<dt title="' + AYUDA_KM_MESS + '">KM Mess aproximados <i class="fas fa-circle-info uv-ayuda"></i></dt><dd>' + fmtKm(v.km_mess) + '</dd>'
        +     '<dt title="' + AYUDA_KM_PERSONALES + '">KM personales <i class="fas fa-circle-info uv-ayuda"></i></dt><dd>' + fmtKm(v.km_personales) + '</dd>'
        +     '<dt>$ en gasolina</dt><dd>MXN ' + fmtMoneda(v.gasto_gas) + '</dd>'
        +   '</dl>'
        + '</div>'
        + '<div class="uv-refs" title="Check-ins del rango">'
        +   '<div><b>' + (v.ots || 0) + '</b>OTs</div><div><b>' + (v.ovs || 0) + '</b>OVs</div><div><b>' + (v.otros || 0) + '</b>Otros</div>'
        + '</div>'
        + '<div class="uv-rend" title="Gasolina del rango ÷ KM Mess">'
        +   '<div class="uv-rend__lbl">Rendimiento</div>'
        +   '<div class="uv-rend__val">' + (v.rendimiento !== null ? 'MXN ' + fmtMoneda(v.rendimiento) + '/km' : '—') + '</div>'
        +   (v.rendimiento === null
              ? '<div class="small text-muted">' + (!v.gasto_gas ? 'Sin cargas de gasolina en el rango' : 'Sin KM Mess en el rango') + '</div>'
              : '')
        + '</div>'
        + '</div>'
    );
    // Muchas rutas de foto_general están mal: sin foto, el ícono.
    $('#uvFicha .uv-foto img').on('error', function () { $(this).replaceWith(ICONO_AUTO); });
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
